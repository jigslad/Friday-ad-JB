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
use Fa\Bundle\MessageBundle\Repository\MessageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to send contact request to moderation.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SendContactReportForModerationCommand extends ContainerAwareCommand
{
    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:contact-report-for-moderation')
        ->setDescription("Read moderation queue and send contact for moderation")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('messageSpammerId', null, InputOption::VALUE_OPTIONAL, 'Message ids', null)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Status to handle', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Send ad for moderation

Command:
 - php app/console fa:send:contact-report-for-moderation
 - php app/console fa:send:contact-report-for-moderation --messageSpammerId="xxxx"

EOF
        );
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searchParam = array();

        //get options passed in command
        $messageSpammerIds = $input->getOption('messageSpammerId');
        $offset     = $input->getOption('offset');
        $status     = $input->getOption('status');

        if ($messageSpammerIds) {
            $messageSpammerIds = explode(',', $messageSpammerIds);
            $messageSpammerIds = array_map('trim', $messageSpammerIds);
            $searchParam['message_spammer']['id'] = $messageSpammerIds;
        } else {
            $messageSpammerIds = null;
            if ($status) {
                $searchParam['message_spammer']['status'] = $status;
            } else {
                $searchParam['message_spammer']['status'] = MessageRepository::MODERATION_QUEUE_STATUS_SEND;
            }
        }

        if (isset($offset)) {
            $this->sendContactReportForModerationWithOffset($searchParam, $input, $output);
        } else {
            $this->sendContactReportForModeration($searchParam, $input, $output);
        }
    }

    /**
     * Send contact for moderation with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendContactReportForModerationWithOffset($searchParam, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getContactReportModerateQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $contactModerates = $qb->getQuery()->getResult();

        foreach ($contactModerates as $contactModerate) {
            $buildRequest      = $this->getContainer()->get('fa_message_spammer.contact_moderation.request_build');
            $moderationRequest = $buildRequest->init($contactModerate);

            //print_r($moderationRequest);

            $moderationRequest = json_encode($moderationRequest);

            /*echo "\n";
            echo $moderationRequest;
            echo "\n";*/

            if ($buildRequest->sendRequest($moderationRequest)) {
                $contactModerate->setStatus(MessageRepository::MODERATION_QUEUE_STATUS_OKAY);
                $this->getContainer()->get('doctrine')->getManager()->persist($contactModerate);
                $output->writeln('Message spammer has been sent for moderation for message id: '.$contactModerate->getId(), true);
            }
        }

        $this->getContainer()->get('doctrine')->getManager()->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send contact for moderation.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendContactReportForModeration($searchParam, $input, $output)
    {
        $count     = $this->getContactReportModerateCount($searchParam);
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:send:contact-report-for-moderation '.$commandOptions;
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
     * Get query builder for Contact moderate.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getContactReportModerateQueryBuilder($searchParam)
    {
        $entityManager     = $this->getContainer()->get('doctrine')->getManager();
        $messageSpammerRepository = $entityManager->getRepository('FaMessageBundle:MessageSpammer');

        $data                  = array();
        $data['query_filters'] = $searchParam;

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($messageSpammerRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get count for Contact to be moderated.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getContactReportModerateCount($searchParam)
    {
        $qb = $this->getContactReportModerateQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
