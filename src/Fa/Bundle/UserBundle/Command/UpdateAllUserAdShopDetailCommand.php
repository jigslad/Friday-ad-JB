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
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\UserBundle\Repository\UserPackageRepository;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\UserSiteRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to add/update/delete solr index for user's shop detail.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAllUserAdShopDetailCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:all-user-shop-detail-solr-index')
        ->setDescription("Update all solr index for users ad shop.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Add/Update/Delete solr index with user information
- Can be run to add/update/delete specific user information to solr index

Command:
 - php app/console fa:update:all-user-shop-detail-solr-index
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
        $solrClient = $this->getContainer()->get('fa.solr.client.user.shop.detail');
        if (!$solrClient->ping()) {
            $output->writeln('Solr service is not available. Please start it.', true);
            return false;
        }

        //get options passed in command
        $offset   = $input->getOption('offset');
        $status   = array('A');

        $statusId = array();
        foreach ($status as $code) {
            if ($code == 'A') {
                $statusId[] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::USER_STATUS_ACTIVE_ID;
            }
        }

        $entityManager  = $this->getContainer()->get('doctrine')->getManager();
        $shopPackageCategoryIds       = array(CategoryRepository::FOR_SALE_ID, CategoryRepository::MOTORS_ID, CategoryRepository::JOBS_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::ANIMALS_ID, CategoryRepository::COMMUNITY_ID);
        $searchParam['status']        = $statusId;
        $searchParam['shop_packages'] = $entityManager->getRepository('FaPromotionBundle:Package')->getShopPackageIdsArrayByCategoryForProfileExposure($shopPackageCategoryIds);

        if (isset($offset)) {
            $this->updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output);
        } else {
            $this->updateSolrIndex($solrClient, $searchParam, $input, $output);
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param object $solrClient  Solr service object.
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output)
    {
        $qb          = $this->getUserPackageQueryBuilder($searchParam);
        $step        = 1;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $userPackages = $qb->getQuery()->getResult();

        foreach ($userPackages as $userPackage) {
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value && $option != 'offset') {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            $commandOptions .= ' --user_id='.$userPackage['id'];
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:user-ad-shop-detail'.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update solr index.
     *
     * @param object $solrClient  Solr service object.
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateSolrIndex($solrClient, $searchParam, $input, $output)
    {
        $count     = $this->getUserCount($searchParam);
        $step      = 1;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:all-user-shop-detail-solr-index'.$commandOptions;
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
     * Get query builder for users.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserPackageQueryBuilder($searchParam)
    {
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();
        $userPackageRepository = $entityManager->getRepository('FaUserBundle:UserPackage');

        $qb = $userPackageRepository->createQueryBuilder(UserPackageRepository::ALIAS)
            ->select('DISTINCT '.UserRepository::ALIAS.'.id')
            ->leftJoin(UserPackageRepository::ALIAS.'.user', UserRepository::ALIAS)
            ->innerJoin('Fa\Bundle\AdBundle\Entity\Ad', AdRepository::ALIAS, 'WITH', UserRepository::ALIAS.'.id = '.AdRepository::ALIAS.'.user')
            ->andWhere(UserRepository::ALIAS.'.status IN (:userStatus)')
            ->setParameter('userStatus', $searchParam['status'])
            ->andWhere(UserPackageRepository::ALIAS.'.status = :status')
            ->setParameter('status', 'A')
            ->andWhere(AdRepository::ALIAS.'.status = :adStatus')
            ->setParameter('adStatus', EntityRepository::AD_STATUS_LIVE_ID);

        if (isset($searchParam['shop_packages']) && count($searchParam['shop_packages'])) {
            $qb->andWhere(UserPackageRepository::ALIAS.'.package IN (:shopPackages)')
            ->setParameter('shopPackages', $searchParam['shop_packages']);
        }

        return $qb;
    }

    /**
     * Get query builder for users.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserCount(array $searchParam)
    {
        $qb = $this->getUserPackageQueryBuilder($searchParam);
        $qb->select('COUNT( DISTINCT '.UserRepository::ALIAS.'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
