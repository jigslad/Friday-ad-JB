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
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send email to users whose ads have low enquiries.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SendLowEnquiryAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send-low-enquiry-alert')
        ->setDescription("Send low enquiry alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Just use for testing purpose. will not use any condition', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send email to users whose ads have low enquiries.

Command:
 - php app/console fa:send-low-enquiry-alert
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
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //get options passed in command
        $offset = $input->getOption('offset');

        $searchParam       = array();
        $searchParam['ad'] = array(
                                 'status'         => \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID,
                                 'is_detached_ad' => 0,
                                 'is_feed_ad'     => 0,
                                 'is_blocked_ad'  => 0,
                             );

        if ($input->getOption('ad_id') > 0) {
            $searchParam['ad']['id'] =  $input->getOption('ad_id');
        } else {
            $identifier = 'low_enquiries_boost_response';
            $parameters = $this->em->getRepository('FaEmailBundle:EmailTemplate')->getSchedualParameterArray($identifier, CommonManager::getCurrentCulture($this->getContainer()));
            $hours      = isset($parameters['advert_within_x_hours']) && $parameters['advert_within_x_hours'] > 0 ? $parameters['advert_within_x_hours'] : 144;

            // Get ads which publised before 6 days (144 hours) and check for ad enquiries.
            $date = date('Y-m-d', strtotime('-'.($hours + 24).' hours'));
            $searchParam['ad']['published_at_from_to'] =  $date.'|'.$date;
        }

        if (isset($offset)) {
            $this->sendEmailForLowEnquiryAdsWithOffset($searchParam, $input, $output);
        } else {
            $this->sendEmailForLowEnquiryAds($searchParam, $input, $output);
        }
    }

    /**
     * Send email ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendEmailForLowEnquiryAdsWithOffset($searchParam, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getAdQueryBuilder($searchParam);
        $step          = 1000;
        $offset        = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();
        foreach ($ads as $ad) {
            $adLowEnquiryimit = $this->em->getRepository('FaCoreBundle:ConfigRule')->getEnquiryLimit($ad->getCategory()->getId(), $this->getContainer());
            $adViewCount      = $this->em->getRepository('FaAdBundle:AdViewCounter')->getTotalHitViewCountByAdAndTime($ad->getPublishedAt(), $ad->getId());
            $adMessages       = $this->em->getRepository('FaMessageBundle:Message')->getAdTotalMessageArrayByAdId($ad->getUser()->getId(), $ad->getId(), $ad->getPublishedAt());
            $adMessageCount   = isset($adMessages[$ad->getId()]) ? $adMessages[$ad->getId()] : 0;

            $adPublishedAt = CommonManager::getTimeStampFromStartDate(date('d/m/Y', $ad->getPublishedAt()));
            $adEnquiryReportDailyRepository = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdEnquiryReportDaily');
            $adCallClicks      = $adEnquiryReportDailyRepository->getAdTotalCallClicksByAdId($ad->getId(), $adPublishedAt);
            $adCallClicksCount = isset($adCallClicks[$ad->getId()]) ? $adCallClicks[$ad->getId()] : 0;

            $adEnquriesCount = $adMessageCount + $adCallClicksCount;
            if ($adEnquriesCount < $adLowEnquiryimit) {
                $this->em->getRepository('FaAdBundle:Ad')->sendEmailForLowEnquiryAlert($ad, $this->getContainer(), $adViewCount, $adMessageCount, $adCallClicksCount);
                echo 'Email has been sent for ad id ->'.$ad->getId()."\n";
            }
        }

        $entityManager->flush();
        $entityManager->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Send email for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendEmailForLowEnquiryAds($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 1000;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
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

            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:send-low-enquiry-alert '.$commandOptions;
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
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array ('id' => 'asc'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdCount($searchParam)
    {
        $qb = $this->getAdQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
