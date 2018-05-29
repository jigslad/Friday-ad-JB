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
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\AdBundle\Entity\Ad;

/**
 * This command is used to update user ad package.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateActiveAdPackageCommand extends ContainerAwareCommand
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
        ->setName('fa:update:active-ad-package')
        ->setDescription("Update user ad statistics.")
        ->addOption('ad_id', null, InputOption::VALUE_OPTIONAL, 'Ad id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update active ad package.

Command:
 - php app/console fa:update:active-ad-package --ad_id=1
 - php app/console fa:update:active-ad-package
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
        $this->mainDbName = $this->getContainer()->getParameter('database_name');
        //get arguments passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateActiveAdYacNumberWithOffset($input, $output);
        } else {
            $this->updateActiveAdYacNumber($input, $output);
        }
    }

    /**
     * Update user ad package.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateActiveAdYacNumber($input, $output)
    {
        $adId    = $input->getOption('ad_id');
        $count     = $this->getActiveTotalAdCount($adId);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads: '.$count, true);
        for ($i = 0; $i <= $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:active-ad-package '.$commandOptions.' --verbose';
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
     * Update user total ad count with offset.
     *
     * @param object $input  Input object
     * @param object $output Output object
     */
    protected function updateActiveAdYacNumberWithOffset($input, $output)
    {
        $adUserPackageUpsellRepository  = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell');
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $offset             = $input->getOption('offset');
        $adId               = $input->getOption('ad_id');

        $adUserPackages = $this->getActiveAdCountResult($adId, $offset, $this->limit);

        foreach ($adUserPackages as $adUserPackage) {
            $adUserPackageUpsells = $adUserPackageUpsellRepository->findBy(array('ad_user_package' => $adUserPackage->getId()));
            $adUserPackageUpsellIds = array();
            foreach ($adUserPackageUpsells as $adUserPackageUpsell) {
                $adUserPackageUpsellIds[] = $adUserPackageUpsell->getUpsell()->getId();
            }
            $adObj = null;
            if ($adUserPackageUpsells) {
                $adObj = $adRepository->find($adUserPackageUpsells[0]->getAdId());
            }
            if ($adUserPackageUpsells && $adUserPackage->getPackage() && $adUserPackage->getPackage()->getUpsells() && count($adUserPackageUpsells) != count($adUserPackage->getPackage()->getUpsells())) {
                foreach ($adUserPackage->getPackage()->getUpsells() as $upsell) {
                    if (!in_array($upsell->getId(), $adUserPackageUpsellIds)) {
                        $adUserPackageUpsellObj = new AdUserPackageUpsell();
                        $adUserPackageUpsellObj->setAdId($adUserPackageUpsells[0]->getAdId());
                        $adUserPackageUpsellObj->setUpsell($upsell);
                        $adUserPackageUpsellObj->setAdUserPackage($adUserPackage);
                        $adUserPackageUpsellObj->setValue($upsell->getValue());
                        $adUserPackageUpsellObj->setValue1($upsell->getValue1());

                        if ($upsell->getDuration()) {
                            $adUserPackageUpsellObj->setExpiresAt(CommonManager::getTimeFromDuration($upsell->getDuration(), $adUserPackageUpsells[0]->getStartedAt()));
                        } elseif ($adObj) {
                            $expirationDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($adObj->getCategory()->getId());
                            $adUserPackageUpsell->setExpiresAt(CommonManager::getTimeFromDuration($expirationDays.'d', $adUserPackageUpsells[0]->getStartedAt()));
                        }

                        $adUserPackageUpsellObj->setStartedAt($adUserPackageUpsells[0]->getStartedAt());
                        $adUserPackageUpsellObj->setCreatedAt($adUserPackageUpsells[0]->getCreatedAt());
                        $adUserPackageUpsellObj->setStatus($adUserPackageUpsells[0]->getStatus());
                        $adUserPackageUpsellObj->setDuration($upsell->getDuration());
                        $adUserPackageUpsellObj->setAdMain($adUserPackageUpsells[0]->getAdMain());
                        $this->em->persist($adUserPackageUpsellObj);
                    }
                }
                $this->em->flush();
                $this->updateAdToSolr($adObj);
                $output->writeln('Upsells added for ad id: '.$adObj->getId());
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for ads.
     *
     * @param integer $adId Ad id.
     *
     * @return integer
     */
    protected function getActiveAdQueryBuilder($adId)
    {
        $adUserPackageRepository  = $this->em->getRepository('FaAdBundle:AdUserPackage');
        $packageRepository  = $this->em->getRepository('FaPromotionBundle:Package');
        $query = $packageRepository->getBaseQueryBuilder()
        ->innerJoin(PackageRepository::ALIAS.'.upsells', UpsellRepository::ALIAS)
        ->andWhere(UpsellRepository::ALIAS.'.id IN (:upsellIds)')
        ->setParameter('upsellIds', $this->em->getRepository('FaPromotionBundle:Upsell')->getPrintPublicationUpsellIdsArray());

        $packages = $query->getQuery()->getResult();

        $packageIds = array();
        foreach ($packages as $package) {
            $packageIds[] = $package->getId();
        }
        $packageIds = array_unique($packageIds);
        $packageRepository->clear();

        $query = $adUserPackageRepository->getBaseQueryBuilder()
        ->andWhere(AdUserPackageRepository::ALIAS.'.status IN (1,2)')
        ->andWhere(AdUserPackageRepository::ALIAS.'.package IN (:packageIds)')
        ->setParameter('packageIds', $packageIds)
        ->andWhere(AdUserPackageRepository::ALIAS.'.created_at >= :created_at')
        ->setParameter('created_at', CommonManager::getTimeStampFromStartDate(date('Y-m-d', strtotime('2016-01-04'))));

        if ($adId) {
            $query->andWhere(AdUserPackageRepository::ALIAS.'.ad_id = :adId')
            ->setParameter('adId', $adId);
        }

        return $query;
    }

    /**
     * Get count for active ads.
     *
     * @param integer $adId Ad id.
     *
     * @return integer
     */
    protected function getActiveTotalAdCount($adId)
    {
        $query = $this->getActiveAdQueryBuilder($adId);

        $query->select('COUNT('.AdUserPackageRepository::ALIAS.'.id) as ad_user_package_count');

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get active ad count results.
     *
     * @param integer $adId   Ad id.
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getActiveAdCountResult($adId, $offset, $limit)
    {
        $query = $this->getActiveAdQueryBuilder($adId);
        $query->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getResult();
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
