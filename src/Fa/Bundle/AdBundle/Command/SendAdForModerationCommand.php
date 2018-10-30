<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdBundle\Repository\AdModerateRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to send ad to moderation.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SendAdForModerationCommand extends ContainerAwareCommand
{
    /**
     * Configure command parameters.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:ad-for-moderation')
        ->setDescription("Read moderation queue and send ad for moderation")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('adId', null, InputOption::VALUE_OPTIONAL, 'Ad ids', null)
        ->addOption('isForManualModeration', null, InputOption::VALUE_OPTIONAL, 'Whether ad requires manual moderation or not', null)
        ->addOption('manualModerationReason', null, InputOption::VALUE_OPTIONAL, 'Reason why ad requires manual moderation', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at regular intervals.

Actions:
- Send ad for moderation

Command:
 - php app/console fa:send:ad-for-moderation
 - php app/console fa:send:ad-for-moderation --adId="xxxx"
 - php app/console fa:send:ad-for-moderation --adId="xxxx" --isForManualModeration='true' --manualModerationReason='xxxx'

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
        $adIds                  = $input->getOption('adId');
        $isForManualModeration  = $input->getOption('isForManualModeration');
        $manualModerationReason = $input->getOption('manualModerationReason');
        $offset                 = $input->getOption('offset');

        if (!$isForManualModeration) {
            $isForManualModeration = false;
        }

        if (!$manualModerationReason) {
            $manualModerationReason = '';
        }

        if ($adIds) {
            $adIds = explode(',', $adIds);
            $adIds = array_map('trim', $adIds);
            $searchParam['ad']['id'] = $adIds;
        } else {
            $adIds = null;
            $searchParam['ad_moderate']['moderation_queue'] = AdModerateRepository::MODERATION_QUEUE_STATUS_SEND;
        }

        if (isset($offset)) {
            $this->sendAdForModerationWithOffset($searchParam, $input, $output, $isForManualModeration, $manualModerationReason);
        } else {
            $this->sendAdForModeration($searchParam, $input, $output, $isForManualModeration, $manualModerationReason);
        }
    }

    /**
     * Send ad for moderation with given offset.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     * @param object $manualModerationReason
     */
    protected function sendAdForModerationWithOffset($searchParam, $input, $output, $isForManualModeration, $manualModerationReason)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAdModerateQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $adModerates = $qb->getQuery()->getResult();

        foreach ($adModerates as $adModerate) {
            $buildRequest      = $this->getContainer()->get('fa_ad.moderation.request_build');
            $moderationRequest = $buildRequest->init($adModerate->getAd(), $adModerate->getValue(), $isForManualModeration, $manualModerationReason);

            //print_r($moderationRequest);

            $moderationRequest = json_encode($moderationRequest);

            //echo "\n";
            //echo $moderationRequest;
            //echo "\n";

            if ($buildRequest->sendRequest($moderationRequest)) {
                $adModerate->setModerationQueue(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT);
                $this->getContainer()->get('doctrine')->getManager()->persist($adModerate);

                $ad = $adModerate->getAd();
                $ad->setType($this->getContainer()->get('doctrine')->getManager()->getReference('FaEntityBundle:Entity', EntityRepository::AD_STATUS_IN_MODERATION_ID));
                $this->getContainer()->get('doctrine')->getManager()->persist($ad);
            }
        }

        $this->getContainer()->get('doctrine')->getManager()->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send ad for moderation.
     *
     * @param array  $searchParam            Search parameters.
     * @param object $input                  Input object.
     * @param object $output                 Output object.
     * @param object $isForManualModeration
     * @param object $manualModerationReason
     */
    protected function sendAdForModeration($searchParam, $input, $output, $isForManualModeration, $manualModerationReason)
    {
        $count     = $this->getAdModerateCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:ad-for-moderation '.$commandOptions;
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
     * Get query builder for ad moderate.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdModerateQueryBuilder($searchParam)
    {
        $entityManager         = $this->getContainer()->get('doctrine')->getManager();
        $adModerateRepository  = $entityManager->getRepository('FaAdBundle:AdModerate');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad_moderate' => array('created_at' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adModerateRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get count for Ad to be moderated.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdModerateCount($searchParam)
    {
        $qb = $this->getAdModerateQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
