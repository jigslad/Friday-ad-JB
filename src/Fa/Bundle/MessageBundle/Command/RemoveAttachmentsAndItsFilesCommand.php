<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\MessageBundle\Repository\NotificationMessageEventRepository;
use Fa\Bundle\MessageBundle\Repository\MessageAttachmentsRepository;

/**
 * This command is used to delete expired notifications
 *
 * php app/console fa:recurring-subscription
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class RemoveAttachmentsAndItsFilesCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:remove-attachments-and-its-files')
        ->setDescription('Remove attachments and its images')
        ->addOption('opt', null, InputOption::VALUE_REQUIRED, 'option which attachments should be removed', "real")
        ->addOption('days', null, InputOption::VALUE_REQUIRED, 'how many days old records to be deleted.', "180")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
<<<EOF
Cron: To be setup.

Actions:
- Remove old attachments and its files, also remove temperory attachments records and its files.

Command:
 - php app/console fa:remove-attachments-and-its-files
EOF
            );
    }


    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $offset               = $input->getOption('offset');
        $searchParams         = array();
        $searchParams['opt']  = $input->getOption('opt');
        $searchParams['days'] = $input->getOption('days');

        if (isset($offset)) {
            $this->removeAttachmentsWithOffset($searchParams, $input, $output, $offset);
        } else {
            $this->removeAttachments($searchParams, $input, $output);
        }
    }

    /**
     * Send contact for moderation with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function removeAttachments($searchParams, $input, $output)
    {
        $count     = $this->getOldAttachmentsTotalCount($searchParams);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('',true);
        $output->writeln('Total attachments to be deleted: '.$count, true);
        $output->writeln('',true);
        for ($i = 0; $i < $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    if ($option == 'criteria') {
                        $commandOptions .= ' --'.$option.'=\''.$value.'\'';
                    } else {
                        $commandOptions .= ' --'.$option.'="'.$value.'"';
                    }
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:remove-attachments-and-its-files '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    /**
     * Send contact for moderation with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function removeAttachmentsWithOffset($searchParams, $input, $output, $offset)
    {
        $removeAttachmentIds = array();
        $objAttachments      = $this->getOldAttachments($searchParams, $offset);

        if ($objAttachments) {
            foreach ($objAttachments As $objAttachment) {
                $webPath       = $this->getContainer()->get('kernel')->getRootDir().'/../web';
                $fileExtension = substr(strrchr($objAttachment->getOriginalFileName(),'.'),1);
                $fileName      = $objAttachment->getSessionId().'_'.$objAttachment->getHash().'.'.$fileExtension;
                $filePath      = $webPath.DIRECTORY_SEPARATOR.$objAttachment->getPath().'/'.$fileName;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $removeAttachmentIds[] = $objAttachment->getId();
            }
            $this->em->getRepository('FaMessageBundle:MessageAttachments')->removeMessageAttachment($removeAttachmentIds);
        }
        $output->writeln('Attachment records and files was deleted successfully.');

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get expired notifications.
     *
     * @param array   $searchParams search parameters.
     * @param Integer $offset record start number.
     */
    public function getOldAttachments($searchParams, $offset)
    {
        $days                = $searchParams['days'];
        $daysBeforeTimestamp = strtotime(date('Y-m-d') . ' -'.$days.' day');

        $q = $this->em->getRepository('FaMessageBundle:MessageAttachments')->createQueryBuilder(MessageAttachmentsRepository::ALIAS);
        $q->andWhere(MessageAttachmentsRepository::ALIAS.'.created_at < :createdAt');
        $q->setParameter('createdAt', $daysBeforeTimestamp);
        $q->addOrderBy(MessageAttachmentsRepository::ALIAS.'.id');

        if ($searchParams['opt'] == 'temp') {
            $q->andWhere(MessageAttachmentsRepository::ALIAS.'.message IS NULL');
        } else {
            $q->andWhere(MessageAttachmentsRepository::ALIAS.'.message IS NOT NULL');
        }

        $q->setMaxResults($this->limit);
        $q->setFirstResult($offset);

        return $q->getQuery()->getResult();
    }

    /**
     * Get expired notifications.
     *
     * @param array   $searchParams search parameters.
     * @param Integer $offset record start number.
     */
    public function getOldAttachmentsTotalCount($searchParams)
    {
        $days                = $searchParams['days'];
        $daysBeforeTimestamp = strtotime(date('Y-m-d') . ' -'.$days.' day');

        $q = $this->em->getRepository('FaMessageBundle:MessageAttachments')->createQueryBuilder(MessageAttachmentsRepository::ALIAS);
        $q->select('COUNT('.MessageAttachmentsRepository::ALIAS.'.id) As TotalCount');
        $q->andWhere(MessageAttachmentsRepository::ALIAS.'.created_at < :createdAt');
        $q->setParameter('createdAt', $daysBeforeTimestamp);
        $q->addOrderBy(MessageAttachmentsRepository::ALIAS.'.id');

        if ($searchParams['opt'] == 'temp') {
            $q->andWhere(MessageAttachmentsRepository::ALIAS.'.message IS NULL');
        } else {
            $q->andWhere(MessageAttachmentsRepository::ALIAS.'.message IS NOT NULL');
        }

        $result = $q->getQuery()->getResult();

        if ($result) {
            return $result[0]['TotalCount'];
        } else {
            return 0;
        }
    }
}
