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

/**
 * This command is used to add/update/delete solr index for user's shop detail.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserShopDetailSolrIndexCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:user-shop-detail-solr-index')
        ->setDescription("Update solr index for users.")
        ->addArgument('action', InputArgument::OPTIONAL, 'add or update or delete', 'add')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'User status', 'A')
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'User ids', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Add/Update/Delete solr index with user information
- Can be run to add/update/delete specific user information to solr index

Command:
 - php app/console fa:update:user-shop-detail-solr-index --status="A" add
 - php app/console fa:update:user-shop-detail-solr-index --status="A" --id="xxxx" update
 - php app/console fa:update:user-shop-detail-solr-index --status="A" --id="xxxx" add
 - php app/console fa:update:user-shop-detail-solr-index --id="xxxx" delete
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

        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $ids      = $input->getOption('id');
        $status   = $input->getOption('status');
        $offset   = $input->getOption('offset');

        if ($ids) {
            $ids = explode(',', $ids);
            $ids = array_map('trim', $ids);
        } else {
            $ids = null;
        }

        if ($status) {
            $status = explode(',', $status);
        } else {
            $status = array('A');
        }

        $statusId = array();
        foreach ($status as $code) {
            if ($code == 'A') {
                $statusId[] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::USER_STATUS_ACTIVE_ID;
            }
        }

        if ($action == 'add' || $action == 'update') {
            $entityManager  = $this->getContainer()->get('doctrine')->getManager();
            $shopPackageCategoryIds       = array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID);
            $searchParam['user']          = $ids;
            $searchParam['status']        = $statusId;
            $searchParam['shop_packages'] = $entityManager->getRepository('FaPromotionBundle:Package')->getShopPackageIdsArrayByCategoryForProfileExposure($shopPackageCategoryIds);

            if (isset($offset)) {
                $this->updateSolrIndexWithOffset($solrClient, $searchParam, $input, $output);
            } else {
                $this->updateSolrIndex($solrClient, $searchParam, $input, $output);
            }
        } elseif ($action == 'delete') {
            $solr = $solrClient->connect();

            if ($ids && is_array($ids)) {
                $solr->deleteByIds($ids);
            } else {
                $solr->deleteByQuery('*');
            }

            $solr->commit();
            $solr->optimize();

            if ($ids && is_array($ids)) {
                $output->writeln('Solr index removed for user id: '.join(',', $ids), true);
            } else {
                $output->writeln('Solr index removed for all users.', true);
            }

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
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getUserPackageQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $userPackages = $qb->getQuery()->getResult();

        $userShopDetailSolrIndex = $this->getContainer()->get('fa.user.shop.detail.solrindex');
        foreach ($userPackages as $userPackage) {
            $user       = $userPackage->getUser();
            $idsFound[] = $user->getId();
            if ($userShopDetailSolrIndex->update($solrClient, $user, $this->getContainer(), true)) {
                $output->writeln('Solr index updated for user id: '.$user->getId(), true);
            } else {
                $output->writeln('Solr index not updated for user id: '.$user->getId(), true);
            }
        }

        if (isset($searchParam['user']['id'])) {
            $idsNotFound = array_diff($searchParam['user']['id'], $idsFound);
        }

        $solr = $solrClient->connect();
        if (count($idsNotFound) > 0) {
            $solr->deleteByIds($idsNotFound);
        }
        $solr->commit();
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:user-shop-detail-solr-index'.$commandOptions.' '.$input->getArgument('action');
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
            ->select(UserRepository::ALIAS, UserPackageRepository::ALIAS)
            ->leftJoin(UserPackageRepository::ALIAS.'.package', PackageRepository::ALIAS)
            ->leftJoin(UserPackageRepository::ALIAS.'.user', UserRepository::ALIAS)
            ->andWhere(UserRepository::ALIAS.'.status IN (:userStatus)')
            ->setParameter('userStatus', $searchParam['status'])
            ->andWhere(UserPackageRepository::ALIAS.'.status = :status')
            ->setParameter('status', 'A');

        if (isset($searchParam['user']) && count($searchParam['user'])) {
            $qb->andWhere(UserPackageRepository::ALIAS.'.user IN (:user)')
                ->setParameter('user', $searchParam['user']);
        }

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
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
