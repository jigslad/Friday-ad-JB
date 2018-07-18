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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to send ad edit live alert
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdEditLiveAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:ad-edit-live-alert')
        ->setDescription("Send ad renewal alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send ad edit live alert to users

Command:
 - php app/console fa:send:ad-edit-live-alert
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

        $offset = $input->getOption('offset');

        $searchParam                     = array();
        $searchParam['entity_ad_status'] = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));
        $to = time() - (10*60);
        $searchParam['ad']['ad_edit_moderated_at_from_to'] =  '|'.$to;
        // Skip detached ads for sending ad edit live mail
        $searchParam['ad']['is_detached_ad'] = 0;
        $searchParam['ad']['is_feed_ad']     = 0;
        $searchParam['ad']['is_blocked_ad']  = 0;

        if (isset($offset)) {
            $this->sendAdEditLiveAlertWithOffset($searchParam, $input, $output);
        } else {
            $output->writeln('Total ads:'.$this->getAdCount($searchParam), true);
            $this->sendAdEditLiveAlert($searchParam, $input, $output);
        }
    }

    /**
     * Send ad edit live email with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendAdEditLiveAlertWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();

        $adIdArray = array();
        foreach ($ads as $ad) {
            $adIdArray[] = $ad->getId();
        }
        if (count($adIdArray)) {
            $adPackageArr = $this->em->getRepository('FaAdBundle:AdUserPackage')->getAdActivePackageArrayByAdId($adIdArray);
        }
        foreach ($ads as $ad) {
            try {
                $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
                $user          = ($ad->getUser() ? $ad->getUser() : null);

                //send email only if ad has user and status is active.
                if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                    $packageDetail = (isset($adPackageArr[$ad->getId()]) ? $adPackageArr[$ad->getId()] : null);
                    if ($packageDetail && isset($packageDetail['package_id'])) {
                        if (isset($packageDetail['package_price']) && $packageDetail['package_price'] > 0) {
                            $emailTemplate = 'ad_edit_is_live_paid';
                        } else {
                            $emailTemplate = 'ad_edit_is_live_free';
                        }
                        $adRepository->sendLiveAdPackageEmailForAd($emailTemplate, $ad->getId(), $packageDetail['package_id'], $this->getContainer());
                        $ad->setAdEditModeratedAt(null);
                        $this->em->persist($ad);
                        $output->writeln('Ad edit email sent for AD ID: '.$ad->getId(), true);
                        $this->em->flush();
                    } else {
                        $ad->setAdEditModeratedAt(null);
                        $this->em->persist($ad);
                        $this->em->flush();
                    }
                }
            } catch (\Exception $e) {

            }
        }
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendAdEditLiveAlert($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:ad-edit-live-alert '.$commandOptions;
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
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');

        $data                  = array();
        $data['query_filters'] = $searchParam;
        $data['query_sorter']  = array('ad' => array ('id' => 'asc'));
        $data['static_filters'] = AdRepository::ALIAS.'.ad_edit_moderated_at > 0';

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
