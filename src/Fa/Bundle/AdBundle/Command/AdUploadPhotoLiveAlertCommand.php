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
use Fa\Bundle\AdBundle\Repository\AdImageRepository;

/**
 * This command is used to Send ad live upload photo alert
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdUploadPhotoLiveAlertCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:send:ad-live-upload-photo-alert')
        ->setDescription("Send ad live upload photo alert")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->setHelp(
            <<<EOF
Cron: To be setup.

Actions:
- Send ad live upload photo alert

Command:
 - php app/console fa:send:ad-live-upload-photo-alert
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

        $to = time() - (60*60);
        $searchParam['ad']['original_published_at_from_to'] =  '|'.$to;
        // Skip detached ads for sending ad edit live mail
        $searchParam['ad']['is_detached_ad'] = 0;
        $searchParam['ad']['is_feed_ad']     = 0;
        $searchParam['ad']['is_blocked_ad']  = 0;
        $searchParam['ad']['is_add_photo_mail_sent']  = 0;

        if (isset($offset)) {
            $this->sendAdLiveAddPhotoLiveAlertWithOffset($searchParam, $input, $output);
        } else {
            $this->sendAdLiveAddPhotoLiveAlert($searchParam, $input, $output);
        }
    }

    /**
     * Send ad edit live email with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function sendAdLiveAddPhotoLiveAlertWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 10;
        $offset      = 0;//$input->getOption('offset');

        $qb->groupBy(AdRepository::ALIAS.'.id');
        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();
        $ads = array_filter($ads);
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');

        foreach ($ads as $ad) {
            try {
                $user          = ($ad->getUser() ? $ad->getUser() : null);

                //send email only if ad has user and status is active.
                if ($user && CommonManager::checkSendEmailToUser($user->getId(), $this->getContainer())) {
                    $adRepository->sendLiveAdUploadPhotoEmailForAd($ad->getId(), $this->getContainer());
                    $ad->setIsAddPhotoMailSent(1);
                    $this->em->persist($ad);
                    $this->em->flush();
                    $output->writeln('Ad upload photo email sent for AD ID: '.$ad->getId(), true);
                }
            } catch (\Exception $e) {
                $output->writeln('Error occurred during subtask', true);
                $output->writeln('AD ID: '.$ad->getId().' Exception:'.$e->getMessage(), true);
                $ad->setIsAddPhotoMailSent(1);
                $this->em->persist($ad);
                $this->em->flush();
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
    protected function sendAdLiveAddPhotoLiveAlert($searchParam, $input, $output)
    {
        $count     = $this->getAdCount($searchParam);
        $step      = 10;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total ads : '.$count, true);
        for ($i = 0; $i < $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:send:ad-live-upload-photo-alert '.$commandOptions;
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
        $data['static_filters'] = AdRepository::ALIAS.'.original_published_at > 0';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);
        $qb = $searchManager->getQueryBuilder();
        $qb->andWhere(AdRepository::ALIAS.'.image_count = 0 OR '.AdRepository::ALIAS.'.image_count IS NULL');

        return $qb;
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
        $qb->select('COUNT('.$qb->getRootAlias().'.id) as total_ads')
        ->setMaxResults(1);

        $res = $qb->getQuery()->getOneOrNullResult();

        return (isset($res['total_ads']) ? $res['total_ads'] : 0);
    }
}
