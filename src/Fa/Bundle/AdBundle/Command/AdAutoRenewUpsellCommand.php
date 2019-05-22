<?php
/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
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
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackage;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\PromotionBundle\Entity\Upsell;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserCredit;
use Fa\Bundle\UserBundle\Entity\UserCreditUsed;

/**
 * This command is used to renew ad package automatic.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright  2018 Friday Media Group Ltd
 * @version v1.0
 */
class AdAutoRenewUpsellCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 50;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Default db name
     *
     * @var string
     */
    private $mainDbName;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-auto-renew-upsell')
        ->setDescription("Auto Renew Ad.")
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad id', null)
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to auto-renew ad.

Command:
 - php bin/console fa:update:ad-auto-renew-upsell --ad_id=1
 - php bin/console fa:update:ad-auto-renew-upsell
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

        $offset   = $input->getOption('offset');

        $searchParam                     = array();
        $searchParam['entity_ad_status'] = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));

        // Skip detached ads for auto renew
        $searchParam['ad']['is_detached_ad'] = 0;
        $searchParam['ad']['is_feed_ad']     = 0;
        $searchParam['ad']['is_blocked_ad']  = 0;

        if (isset($offset)) {
            $this->updateAdAutoRenewalWithOffset($searchParam, $input, $output);
        } else {
            $this->updateAdAutoRenewal($searchParam, $input, $output);
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdAutoRenewalWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder($searchParam);
        $step        = 100;
        $offset      = 0;
        $errorMsg    = '';
        $type = 'renew';

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $result) {
            //get basic data
            $ad  = $this->em->getRepository('FaAdBundle:Ad')->find($result[0]['id']);
            if (!empty($ad)) {
                $currentTime = time();
                $adId = $ad->getId();
                
                $adUserPackage  = $this->em->getRepository('FaAdBundle:AdUserPackage')->findOneBy(array('ad_id'=>$adId,'status'=>AdUserPackageRepository::STATUS_ACTIVE));
                //Update AdUserPackage
                if (!empty($adUserPackage)) {
                    if ($adUserPackage->getDuration()) {
                        $expireAt = CommonManager::getTimeFromDuration($adUserPackage->getDuration());
                    } else {
                        $ad = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
                        $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                        $expireAt = CommonManager::getTimeFromDuration($expirationDays.'d');
                    }

                    $adUserPackage->setStartedAt($currentTime);
                    $adUserPackage->setExpiresAt($expireAt);
                    $this->em->persist($adUserPackage);
                    $this->em->flush($adUserPackage);

                    //Update AdUserPackageUpsell
                    $adUserPackageUpsells  = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_user_package'=>$adUserPackage->getId(),'status'=>1));

                    if (!empty($adUserPackageUpsells)) {
                        foreach ($adUserPackageUpsells as $adUserPackageUpsell) {
                            $adUserPackageUpsell->setStatus(1);
                            $adUserPackageUpsell->setStartedAt($currentTime);
                            if ($adUserPackageUpsell->getDuration()) {
                                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($adUserPackageUpsell->getDuration()));
                            } else {
                                $ad = $this->em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adUserPackageUpsell->getAdId()));
                                $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($ad->getCategory()->getId());
                                $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d'));
                            }

                            $this->em->persist($adUserPackageUpsell);
                        }

                        $this->em->flush();
                    }
                }

                $ad->setRenewedAt($currentTime);
                //$ad->setIsRenewalMailSent(0);
                $ad->setSourceLatest('auto-renew');
                $ad->setCreatedAt($currentTime);
                $ad->setExpiresAt($expireAt);
                $isWeeklyRefresh = $this->em->getRepository('FaAdBundle:Ad')->checkIsWeeklyRefreshAd($adId);
                if ($isWeeklyRefresh) {
                    $ad->setWeeklyRefreshAt($currentTime);
                }

                $this->em->persist($ad);
                $this->em->flush($ad);

                // activate yac number
                $this->em->getRepository('FaAdBundle:Ad')->handleAdPrivacyNumber($adId, $this->getContainer());
            }

            $this->em->flush();
            $this->updateAdToSolr($ad);
        }
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdAutoRenewal($searchParam, $input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:ad-auto-renew-upsell '.$commandOptions;
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
        $data['query_sorter']  = array('ad' => array('id' => 'asc'));
        
        $renewdateFrom = strtotime(date('Y-m-d 0:0:0'));
        $renewdateTo = strtotime(date('Y-m-d 23:59:59'));

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

        $queryBuilder = $searchManager->getQueryBuilder();
        $queryBuilder->addSelect('IDENTITY('.AdUserPackageRepository::ALIAS.'.package) as package_id');
        $queryBuilder->leftJoin('FaAdBundle:AdUserPackage', AdUserPackageRepository::ALIAS, 'WITH', AdUserPackageRepository::ALIAS.'.ad_id = '.AdRepository::ALIAS.'.id')
                     ->andWhere(AdUserPackageRepository::ALIAS.'.status = :ad_user_package_status')
                     ->setParameter('ad_user_package_status', AdUserPackageRepository::STATUS_ACTIVE);
        $queryBuilder->leftJoin('FaAdBundle:AdUserPackageUpsell', AdUserPackageUpsellRepository::ALIAS, 'WITH', AdUserPackageUpsellRepository::ALIAS.'.ad_id = '.AdRepository::ALIAS.'.id')
                     ->leftJoin('FaPromotionBundle:Upsell', UpsellRepository::ALIAS, 'WITH', AdUserPackageUpsellRepository::ALIAS.'.upsell = '.UpsellRepository::ALIAS.'.id')
                     ->andWhere(AdUserPackageUpsellRepository::ALIAS.'.expires_at >='. $renewdateFrom)
                     ->andWhere(AdUserPackageUpsellRepository::ALIAS.'.expires_at <='. $renewdateTo)
                     ->andWhere(UpsellRepository::ALIAS.'.type = :ad_user_package_upsell_type')
                     ->setParameter('ad_user_package_upsell_type', UpsellRepository::UPSELL_TYPE_AUTO_RENEW_ID);

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

   
    /**
     * Update solr index.
     *
     * @param Ad $ad
     *
     * return boolean
     */
    public function updateAdToSolr(Ad $ad)
    {
        $solrClient = $this->getContainer()->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $adSolrIndex = $this->getContainer()->get('fa.ad.solrindex');
        return $adSolrIndex->update($solrClient, $ad, $this->getContainer(), false);
    }
}
