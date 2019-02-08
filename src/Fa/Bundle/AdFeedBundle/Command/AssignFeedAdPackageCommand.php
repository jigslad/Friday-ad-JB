<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to update ad report statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AssignFeedAdPackageCommand extends ContainerAwareCommand
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
     * Package id
     *
     * @var array
     */
    private $packageId;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:assign:feed-ad-package')
        ->setDescription("Update ad report statistics.")
        ->addArgument('action', InputArgument::OPTIONAL, 'all or beforeoneday')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to assign feed ad package.

Command:
 - php app/console fa:assign:feed-ad-package
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
        // set entity manager.
        $this->entityManager        = $this->getContainer()->get('doctrine')->getManager();
        $this->packageId    = $this->entityManager->getRepository('FaCoreBundle:ConfigRule')->getClickEditVehicleAdvertsPackageId($this->getContainer());

        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('Total ads: '.$this->getActiveFeedAdCount(), true);
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        // assign feed ad package.
        if (isset($offset)) {
            $this->assignFeedAdPackageWithOffset($input, $output);
        } else {
            $this->assignFeedAdPackage($input, $output);
        }

        if (!isset($offset)) {
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Execute raw query.
     *
     * @param string  $sql           Sql query to run.
     * @param object  $entityManager Entity manager.
     *
     * @return object
     */
    private function executeRawQuery($sql, $entityManager)
    {
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Update user total ad count.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function assignFeedAdPackage($input, $output)
    {
        $count  = $this->getActiveFeedAdCount();
        if ($count > 50000) {
            $count = 50000;
        }

        for ($i = 40000; $i < $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:assign:feed-ad-package '.$commandOptions.' '.$input->getArgument('action');
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
    protected function assignFeedAdPackageWithOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $feedAds = $this->getActiveFeedAdResult($offset, $this->limit);

        foreach ($feedAds as $feedAd) {
            $this->assignOrRemoveAdPackage($feedAd->getAd(), $feedAd->getUser(), $output);
        }
    }

    /**
     * Get query builder for ads.
     *
     * @return count
     */
    protected function getActiveFeedAdCount()
    {
        $adFeedRepository  = $this->entityManager->getRepository('FaAdFeedBundle:AdFeed');

        $query = $adFeedRepository->getBaseQueryBuilder()
            ->select('COUNT('.AdFeedRepository::ALIAS.'.id)')
            ->andWhere(AdFeedRepository::ALIAS.'.status = :status')
            ->andWhere(AdFeedRepository::ALIAS.'.ref_site_id = 1')
            ->setParameter('status', 'A');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get ad count results.
     *
     * @param integer $offset      Offset.
     * @param integer $limit       Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getActiveFeedAdResult($offset, $limit)
    {
        $adFeedRepository  = $this->entityManager->getRepository('FaAdFeedBundle:AdFeed');

        $query = $adFeedRepository->getBaseQueryBuilder()
            ->andWhere(AdFeedRepository::ALIAS.'.status = :status')
            ->andWhere(AdFeedRepository::ALIAS.'.ref_site_id = 1')
            ->setParameter('status', 'A')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getResult();
    }

    /**
     * Assign ad package
     *
     * @param object $ad
     * @param object $user
     */
    protected function assignOrRemoveAdPackage($ad, $user = null, $output)
    {
        try {
            if ($ad) {
                if (isset($this->packageId) && $this->packageId && $ad->getStatus() && $ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID) {
                    $this->handleAdPackage($ad, $user, 'update');
                    $output->writeln('Package assigned to ad:'.$ad->getId(), true);
                } else {
                    $this->handleAdPackage($ad, $user, 'remove');
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Remove or assign package
     *
     * @param object $ad
     * @param object $user
     * @param string $type
     */
    protected function handleAdPackage($ad, $user = null, $type = 'update')
    {
        $deleteManager = $this->getContainer()->get('fa.deletemanager');
        $adId = $ad->getId();
        $adUserPackage = $this->entityManager->getRepository('FaAdBundle:AdUserPackage')->findOneBy(array('ad_id' => $adId, 'status' => AdUserPackageRepository::STATUS_ACTIVE), array('id' => 'DESC'));

        if ($adUserPackage && $type == 'remove') {
            //remove ad use package upsell
            $objAdUserPackageUpsells = $this->entityManager->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
            if ($objAdUserPackageUpsells  && $type == 'remove') {
                foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                    $deleteManager->delete($objAdUserPackageUpsell);
                }
            }
            //remove ad user package
            $deleteManager->delete($adUserPackage);
        } elseif ($type == 'update' && (!$adUserPackage || ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $this->packageId))) {
            if ($adUserPackage && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getId() != $this->packageId) {
                //remove ad use package upsell
                $objAdUserPackageUpsells = $this->entityManager->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
                if ($objAdUserPackageUpsells) {
                    foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                        $deleteManager->delete($objAdUserPackageUpsell);
                    }
                }

                //remove ad user package
                $deleteManager->delete($adUserPackage);
            }

            $this->entityManager->getRepository('FaAdBundle:AdUserPackage')->clear();
            $this->entityManager->getRepository('FaAdBundle:AdUserPackageUpsell')->clear();
            $adUserPackage = new AdUserPackage();

            // find & set package
            $package = $this->entityManager->getRepository('FaPromotionBundle:Package')->find($this->packageId);
            $adUserPackage->setPackage($package);

            // set ad
            $adMain = $this->entityManager->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackage->setAdMain($adMain);
            $adUserPackage->setAdId($adId);
            $adUserPackage->setStatus(AdUserPackageRepository::STATUS_ACTIVE);
            $adUserPackage->setStartedAt(time());
            if ($package->getDuration()) {
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->entityManager->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackage->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            // set user
            if ($user) {
                $adUserPackage->setUser($user);
            }

            $adUserPackage->setPrice($package->getPrice());
            $adUserPackage->setDuration($package->getDuration());
            $this->entityManager->persist($adUserPackage);
            $this->entityManager->flush();
        }

        if (isset($adUserPackage) && $adUserPackage && $adUserPackage->getId()) {
            if ($type == 'update') {
                $packageUpsellIds = array();
                $package = $this->entityManager->getRepository('FaPromotionBundle:Package')->find($this->packageId);
                foreach ($package->getUpsells() as $upsell) {
                    $this->addAdUserPackageUpsell($ad, $adUserPackage, $upsell);
                    $packageUpsellIds[] = $upsell->getId();
                }
                $objAdUserPackageUpsells = $this->entityManager->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId()));
                foreach ($objAdUserPackageUpsells as $objAdUserPackageUpsell) {
                    if (!in_array($objAdUserPackageUpsell->getUpsell()->getId(), $packageUpsellIds)) {
                        $deleteManager->delete($objAdUserPackageUpsell);
                    }
                }
            }
        }

        $isWeeklyRefresh = $this->entityManager->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($ad->getId());

        // Check weekly refresh upsell purchased then set weekly_refresh_at field
        if ($isWeeklyRefresh && !$ad->getWeeklyRefreshAt()) {
            $ad->setWeeklyRefreshAt(time());
        } elseif ($isWeeklyRefresh && $ad->getWeeklyRefreshAt() && $ad->getPublishedAt() && $ad->getWeeklyRefreshAt() < $ad->getPublishedAt()) {
            $ad->setWeeklyRefreshAt($ad->getPublishedAt());
        } elseif ($isWeeklyRefresh === false) {
            $ad->setWeeklyRefreshAt(null);
        }

        $this->entityManager->persist($ad);
        $this->entityManager->flush($ad);
    }

    /**
     * Add ad user package upsell
     *
     * @param object $ad
     * @param object $adUserPackage
     * @param object $upsell
     */
    protected function addAdUserPackageUpsell($ad, $adUserPackage, $upsell)
    {
        $adId = $ad->getId();
        $adUserPackageUpsellObj = $this->entityManager->getRepository('FaAdBundle:AdUserPackageUpsell')->findOneBy(array('ad_id' => $adId, 'ad_user_package' => $adUserPackage->getId(), 'status' => 1, 'upsell' => $upsell->getId()));
        if (!$adUserPackageUpsellObj) {
            $adUserPackageUpsell = new AdUserPackageUpsell();
            $adUserPackageUpsell->setUpsell($upsell);

            // set ad user package id.
            if ($adUserPackage) {
                $adUserPackageUpsell->setAdUserPackage($adUserPackage);
            }

            // set ad
            $adMain = $this->entityManager->getRepository('FaAdBundle:AdMain')->find($adId);
            $adUserPackageUpsell->setAdMain($adMain);
            $adUserPackageUpsell->setAdId($adId);

            $adUserPackageUpsell->setValue($upsell->getValue());
            $adUserPackageUpsell->setValue1($upsell->getValue1());
            $adUserPackageUpsell->setDuration($upsell->getDuration());
            $adUserPackageUpsell->setStatus(1);
            $adUserPackageUpsell->setStartedAt(time());
            if ($upsell->getDuration()) {
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration()));
            } elseif ($ad) {
                $expirationDays = $this->entityManager->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
            }

            $this->entityManager->persist($adUserPackageUpsell);
            $this->entityManager->flush();
        }
    }
}
