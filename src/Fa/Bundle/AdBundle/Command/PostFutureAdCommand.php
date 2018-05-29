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
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to post future ads.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PostFutureAdCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Serach params
     *
     * @var object
     */
    private $searchParams = array();

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:post:future-ad')
        ->setDescription("Post future ads.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad id', null)
        ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date (Y-m-d) for which need to post future ads', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to post future ads.

Command:
 - php app/console fa:post:future-ad  --date="2015-04-28" --ad_id="XXXX"
 - php app/console fa:post:future-ad --date="2015-04-28"
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
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        //get arguments passed in command
        $offset = $input->getOption('offset');
        $adId  = $input->getOption('ad_id');
        if ($adId) {
            $this->searchParams['ad_id'] = $adId;
        }

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        // post future ads.
        if (isset($offset)) {
            $this->postFutureAdsWithOffset($input, $output);
        } else {
            $this->postFutureAds($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * post future ads.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function postFutureAds($input, $output)
    {
        //get arguments passed in command
        $date  = $input->getOption('date');
        $count = $this->getFuturePostAdCount($date);

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
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:post:future-ad '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update user total ad count with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function postFutureAdsWithOffset($input, $output)
    {
        $date   = $input->getOption('date');
        $offset = 0;
        $ads    = $this->getFuturePostAdResult($date, $offset, $this->limit);
        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');
        $adUserPackageRepository = $this->entityManager->getRepository('FaAdBundle:AdUserPackage');

        foreach ($ads as $ad) {
            $adInactivePackage = $adUserPackageRepository->findOneBy(array('ad_id' => $ad->getId(), 'status' => AdUserPackageRepository::STATUS_INACTIVE), array('id' => 'DESC'));
            if ($adInactivePackage) {
                $this->entityManager->beginTransaction();
                try {
                    $adParams = $adRepository->activateAd($ad->getId(), true, true, true, false, 0, $this->getContainer());
                    $ad->setFuturePublishAt(null);
                    $ad->setOldFuturePublishAt(time());
                    $this->entityManager->persist($ad);
                    $this->entityManager->flush($ad);
                    $this->entityManager->commit();

                    try {
                        // send ad package email
                        if (isset($adParams['adUserPackageId']) && $adParams['adUserPackageId']) {
                            $adUserPackageObj = $adUserPackageRepository->findOneBy(array('id' => $adParams['adUserPackageId']));
                            if ($adUserPackageObj && $adUserPackageObj->getPackage() && $adUserPackageObj->getPackage()->getEmailTemplate()) {
                                $adRepository->sendLiveAdPackageEmail($adUserPackageObj->getPackage()->getEmailTemplate()->getIdentifier(), $ad->getId(), $adUserPackageObj->getPackage()->getId(), $this->getContainer());
                            }
                        }
                    } catch (\Exception $e) {
                        CommonManager::sendErrorMail($this->getContainer(), 'Error: Problem in send email post future ad: '.$ad->getId(), $e->getMessage(), $e->getTraceAsString());
                    }
                    $output->writeln('Future ad posted successfully: '.$ad->getId(), true);
                } catch (\Exception $e) {
                    $this->entityManager->getConnection()->rollback();
                    CommonManager::sendErrorMail($this->getContainer(), 'Error: Problem in post future ad: '.$ad->getId(), $e->getMessage(), $e->getTraceAsString());
                }
            } else {
                $ad->setFuturePublishAt(null);
                $ad->setOldFuturePublishAt(time());
                $this->entityManager->persist($ad);
                $this->entityManager->flush($ad);
                $output->writeln('Future post ad no package found for ad id: '.$ad->getId(), true);
            }
        }
    }

    /**
     * Get query builder for future ads.
     *
     * @param string $date Date.
     *
     * @return count
     */
    protected function getFuturePostAdCount($date)
    {
        $query = $this->getAdQuerybuilder($date);
        $query->select('COUNT('.AdRepository::ALIAS.'.id)');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get ad query builder.
     *
     * @param string $date Date.
     *
     * @return Doctrine_Query object.
     */
    protected function getAdQuerybuilder($date)
    {
        $adRepository = $this->entityManager->getRepository('FaAdBundle:Ad');

        $query = $adRepository->getBaseQueryBuilder();

        list($startDate, $endDate) = $this->getDateInTimeStamp($date);
        $query->andWhere(AdRepository::ALIAS.'.future_publish_at BETWEEN '.$startDate.' AND '.$endDate);
        $query->andWhere(AdRepository::ALIAS.'.is_blocked_ad = 0');
        $query->andWhere(AdRepository::ALIAS.'.status = '.EntityRepository::AD_STATUS_SCHEDULED_ADVERT_ID);

        if (count($this->searchParams) && isset($this->searchParams['ad_id'])) {
            $query->andWhere(AdRepository::ALIAS.'.id = :adId')
            ->setParameter('adId', $this->searchParams['ad_id']);
        }

        return $query;
    }

    /**
     * Get fututre ad results.
     *
     * @param string  $date        Date.
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getFuturePostAdResult($date, $offset, $limit)
    {
        $query = $this->getAdQuerybuilder($date);
        $query->setMaxResults($limit)
        ->setFirstResult($offset);

        return $query->getQuery()->getResult();
    }

        /**
     * Get date in time stamp
     *
     * @param string $date Date.
     *
     * @return array
     */
    private function getDateInTimeStamp($date)
    {
        if ($date) {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime($date)));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', strtotime($date)));
        } else {
            $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d'));
            $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d'));
        }

        return array($startDate, $endDate);
    }
}
