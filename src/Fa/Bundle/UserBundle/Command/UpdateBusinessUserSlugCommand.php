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
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\UserSiteRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to add/update/delete solr index for user's shop detail.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateBusinessUserSlugCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:business:user-slug')
        ->setDescription("Update business user slug.")
        ->addArgument('action', InputArgument::REQUIRED, 'generate or remove')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'User status', array('A'))
        ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'User ids', null)
        ->addOption('update_only_null', null, InputOption::VALUE_OPTIONAL, 'Update only null slug', false)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Generate / Remove business user slug command
- Can be run to add/update/delete specific user information to solr index

Command:
 - php app/console fa:update:business:user-slug --status="A" generate
 - php app/console fa:update:business:user-slug --status="A" --id="xxxx" generate
 - php app/console fa:update:business:user-slug --status="A" --id="xxxx" remove
 - php app/console fa:update:business:user-slug --id="xxxx" remove
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
        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $ids      = $input->getOption('id');
        $status   = $input->getOption('status');
        $update_only_null = $input->getOption('update_only_null');
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

        if ($action == 'generate') {
            $searchParam['update_only_null']   = $update_only_null;
            $searchParam['user']   = $ids;
            $searchParam['status'] = $statusId;

            if (isset($offset)) {
                $this->updateBusinessUserSlugWithOffset($searchParam, $input, $output);
            } else {
                $this->updateBusinessUserSlug($searchParam, $input, $output);
            }
        } elseif ($action == 'remove') {
            if ($ids && is_array($ids)) {
                foreach ($ids as $userId) {
                    CommonManager::removeCachePattern($this->getContainer(), $this->getUserTableName().'|getUserProfileSlug|'.$userId.'_*');
                }
                $output->writeln('User slug removed for user id: '.join(',', $ids), true);
            } else {
                CommonManager::removeCachePattern($this->getContainer(), $this->getUserTableName().'|getUserProfileSlug|*');
                $output->writeln('User slug removed for all users.', true);
            }
        }
    }

    /**
     * Update solr index with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateBusinessUserSlugWithOffset($searchParam, $input, $output)
    {
        $qb   = $this->getUserSiteQueryBuilder($searchParam);
        $step = 1000;

        if (isset($searchParam['update_only_null']) && $searchParam['update_only_null']) {
            $offset = 0;
        } else {
            $offset = $input->getOption('offset');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        $businessUsers  = $qb->getQuery()->getResult();
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository('FaUserBundle:User');
        foreach ($businessUsers as $businessUser) {
            $userObj = $businessUser->getUser();
            $userId  = $userObj->getId();
            CommonManager::removeCachePattern($this->getContainer(), $this->getUserTableName().'|getUserProfileSlug|'.$userId.'_*');
            $userRepository->getUserProfileSlug($userId, $this->getContainer(), true, $userObj, $businessUser);
            $output->writeln('User slug generated for user id: '.$userId, true);
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update solr index.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateBusinessUserSlug($searchParam, $input, $output)
    {
        $count     = $this->getUserSiteCount($searchParam);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:business:user-slug'.$commandOptions.' '.$input->getArgument('action');
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
     * Get query builder for users site.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserSiteQueryBuilder($searchParam)
    {
        $entityManager      = $this->getContainer()->get('doctrine')->getManager();
        $userSiteRepository = $entityManager->getRepository('FaUserBundle:UserSite');

        $qb = $userSiteRepository->createQueryBuilder(UserSiteRepository::ALIAS)
            ->leftJoin(UserSiteRepository::ALIAS.'.user', UserRepository::ALIAS)
            ->andWhere(UserRepository::ALIAS.'.status IN (:userStatus)')
            ->setParameter('userStatus', $searchParam['status']);

        if (isset($searchParam['user']) && count($searchParam['user'])) {
            $qb->andWhere(UserSiteRepository::ALIAS.'.user IN (:user)')
                ->setParameter('user', $searchParam['user']);
        }
        if (isset($searchParam['update_only_null']) && $searchParam['update_only_null']) {
            $qb->andWhere(UserSiteRepository::ALIAS.'.slug IS NULL');
        }

        return $qb;
    }

    /**
     * Get query builder for users site.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserSiteCount(array $searchParam)
    {
        $qb = $this->getUserSiteQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get user site table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getUserSiteTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserSite')->getTableName();
    }

    /**
     * Get user table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getUserTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:User')->getTableName();
    }
}
