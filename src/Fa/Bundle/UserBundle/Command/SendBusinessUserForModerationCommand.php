<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send user to moderation.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SendBusinessUserForModerationCommand extends ContainerAwareCommand
{
    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:business-user-for-moderation')
        ->setDescription("Send business user for moderation")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'User ids', null)
        ->addOption('isForManualModeration', null, InputOption::VALUE_OPTIONAL, 'Whether ad requires manual moderation or not', false)
        ->addOption('manualModerationReason', null, InputOption::VALUE_OPTIONAL, 'Reason why ad requires manual moderation', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Send business user for moderation

Command:
 - php app/console fa:send:business-user-for-moderation
 - php app/console fa:send:business-user-for-moderation --userId="xxxx"
 - php app/console fa:send:business-user-for-moderation --userId="xxxx" --isForManualModeration='true' --manualModerationReason='xxxx'

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
        $userIds                  = $input->getOption('userId');
        $isForManualModeration  = $input->getOption('isForManualModeration');
        $manualModerationReason = $input->getOption('manualModerationReason');
        $offset                 = $input->getOption('offset');

        if (!$isForManualModeration) {
            $isForManualModeration = false;
        }

        if (!$manualModerationReason) {
            $manualModerationReason = '';
        }

        if ($userIds) {
            $userIds = explode(',', $userIds);
            $userIds = array_map('trim', $userIds);
            $searchParam['user']['id'] = $userIds;
        } else {
            $userIds = null;
        }

        $searchParam['user']['role'] = RoleRepository::ROLE_BUSINESS_SELLER_ID;

        if (isset($offset)) {
            $this->sendBusinessUserForModerationWithOffset($searchParam, $input, $output, $isForManualModeration, $manualModerationReason);
        } else {
            $this->sendBusinessUserForModeration($searchParam, $input, $output, $isForManualModeration, $manualModerationReason);
        }
    }

    /**
     * Send business user for moderation with given offset.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     * @param object $manualModerationReason
     */
    protected function sendBusinessUserForModerationWithOffset($searchParam, $input, $output, $isForManualModeration, $manualModerationReason)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getBusinessUserQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $users = $qb->getQuery()->getResult();

        foreach ($users as $user) {
            $buildRequest      = $this->getContainer()->get('fa_user.moderation.request_build');
            $moderationRequest = $buildRequest->init($user, 1, $isForManualModeration, $manualModerationReason);

            $moderationRequest = json_encode($moderationRequest);

            if ($buildRequest->sendRequest($moderationRequest)) {
                $output->writeln('User sent for moderation: '.$user->getId(), true);
            }

        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send business user for moderation.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     * @param object $manualModerationReason
     */
    protected function sendBusinessUserForModeration($searchParam, $input, $output, $isForManualModeration, $manualModerationReason)
    {
        $count     = $this->getBusinessUserCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:business-user-for-moderation '.$commandOptions.' -v';
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
     * Get query builder for business user.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getBusinessUserQueryBuilder($searchParam)
    {
        $entityManager   = $this->getContainer()->get('doctrine')->getManager();
        $userRepository  = $entityManager->getRepository('FaUserBundle:User');

        $data                  = array();
        $data['query_filters'] = $searchParam;

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($userRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get count for business user to be moderated.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getBusinessUserCount($searchParam)
    {
        $qb = $this->getBusinessUserQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
