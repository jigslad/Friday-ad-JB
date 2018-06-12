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
class SendContactForModerationCommand extends ContainerAwareCommand
{
    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:contact-for-moderation')
        ->setDescription("Read moderation queue and send contact for moderation")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('messageId', null, InputOption::VALUE_OPTIONAL, 'Message ids', null)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Status to handle', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Send ad for moderation

Command:
 - php app/console fa:send:contact-for-moderation
 - php app/console fa:send:contact-for-moderation --messageId="xxxx"

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
        $messageIds = $input->getOption('messageId');
        $offset     = $input->getOption('offset');
        $status     = $input->getOption('status');

        if ($messageIds) {
            $messageIds = explode(',', $messageIds);
            $messageIds = array_map('trim', $messageIds);
            $searchParam['message']['id'] = $messageIds;
        } else {
            $messageIds = null;
            if ($status) {
                $searchParam['message']['status'] = $status;
            } else {
                $searchParam['message']['status'] = MessageRepository::MODERATION_QUEUE_STATUS_SEND;
            }

            // handle date
            $searchParam['message']['created_at_from_to'] =  strtotime(date('d-m-Y',strtotime("-7 day"))).'|';
        }

        if (isset($offset)) {
            $this->sendContactForModerationWithOffset($searchParam, $input, $output);
        } else {
            $this->sendContactForModeration($searchParam, $input, $output);
        }
    }

    /**
     * Send contact for moderation with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendContactForModerationWithOffset($searchParam, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getContactModerateQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $contactModerates = $qb->getQuery()->getResult();

        foreach ($contactModerates as $contactModerate) {
            $buildRequest      = $this->getContainer()->get('fa_message.contact_moderation.request_build');
            $moderationRequest = $buildRequest->init($contactModerate);

            //print_r($moderationRequest);

            $moderationRequest = json_encode($moderationRequest);

            //echo "\n";
            //echo $moderationRequest;
            //echo "\n";

            if ($buildRequest->sendRequest($moderationRequest)) {
                $contactModerate->setStatus(MessageRepository::MODERATION_QUEUE_STATUS_SENT);
                $this->getContainer()->get('doctrine')->getManager()->persist($contactModerate);
                $output->writeln('Message has been sent for moderation for message id: '.$contactModerate->getId(), true);
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
    protected function sendContactForModeration($searchParam, $input, $output)
    {
        $count     = $this->getContactModerateCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:contact-for-moderation '.$commandOptions;
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
    protected function getContactModerateQueryBuilder($searchParam)
    {
        $entityManager     = $this->getContainer()->get('doctrine')->getManager();
        $messageRepository = $entityManager->getRepository('FaMessageBundle:Message');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('message' => array ('created_at' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($messageRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get count for Contact to be moderated.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getContactModerateCount($searchParam)
    {
        $qb = $this->getContactModerateQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
