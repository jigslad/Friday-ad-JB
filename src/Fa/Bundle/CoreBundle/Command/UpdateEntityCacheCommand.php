<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:update:entity generate FaCoreBundle:Entity FaCoreBundle:Category
 * php app/console fa:update:entity generate
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateEntityCacheCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:entity')
        ->setDescription("Update or Remove entity cache \n Generate single entity => php app/console fa:update:entity generate FaCoreBundle:Entity \n Generate all entity defined in yml=> php app/console fa:update:entity generate \n Generate single entity type => php app/console fa:update:entity generate FaCoreBundle:Entity:Make")
        ->addArgument('action', InputArgument::REQUIRED, 'generate or remove')
        ->addArgument('class', InputArgument::REQUIRED, 'entity bundle name')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "512M")
        ->setHelp(
            <<<EOF
Cron: To be setup to generate and remove entity cache.

Actions:
- Generate cache.

Command:
 - php app/console fa:update:entity generate FaEntityBundle:Category
 - php app/console fa:update:entity generate FaEntityBundle:Location
 - php app/console fa:update:entity generate FaEntityBundle:Entity
 - php app/console fa:update:entity generate FaEntityBundle:Locality
 - php app/console fa:update:entity generate FaPaymentBundle:DeliveryMethodOption
 - php app/console fa:update:entity remove FaEntityBundle:Category
 - php app/console fa:update:entity remove FaEntityBundle:Location
 - php app/console fa:update:entity remove FaEntityBundle:Entity
 - php app/console fa:update:entity remove FaEntityBundle:Locality

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
        $searchParam = array();

        //get arguments passed in command
        $action      = $input->getArgument('action');
        $entityClass = $input->getArgument('class');
        $offset      = $input->getOption('offset');

        //get entity cache manager service
        $entityCacheManager = $this->getContainer()->get('fa.entity.cache.manager');
        if ($action == 'generate') {
            /*if (count($entityClasses)) {
                foreach ($entityClasses as $entityClasse) {
                    $entityCacheManager->generateArray($entityClasse);
                    $output->writeln('Entity generated for: '.$entityClasse);
                }
            }*/
            if (isset($offset)) {
                $this->updateEntityCacheWithOffset($searchParam, $action, $entityClass, $input, $output);
            } else {
                $this->updateEntityCache($searchParam, $action, $entityClass, $input, $output);
            }
        } elseif ($action == 'remove') {
            //remove cache
            CommonManager::removeCachePattern($this->getContainer(), '*EntityCacheManager'.'|'.'getEntityNameById'.'|'.$entityClass.'*');
            $output->writeln('Entity removed for: '.$entityClass);
        }
    }

    /**
     * Update entity cache with offset.
     *
     * @param array   $searchParam    Search parameters.
     * @param string  $action         Whether to generate or remove.
     * @param string  $entityClass    String entity classes.
     * @param object  $input          Input object.
     * @param object  $output         Output object.
     */
    protected function updateEntityCacheWithOffset($searchParam, $action, $entityClass, $input, $output)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $qb            = $this->getQueryBuilder($searchParam, $entityClass);
        $step          = 1000;
        $offset        = $input->getOption('offset');
        $repository    = $entityManager->getRepository($entityClass);

        if ($entityClass == 'FaEntityBundle:Location') {
            $qb->select($repository->getRepositoryAlias().'.id', $repository->getRepositoryAlias().'.name', $repository->getRepositoryAlias().'.url as slug');
        } elseif ($entityClass == 'FaEntityBundle:Locality') {
            $qb->select($repository->getRepositoryAlias().'.id', $repository->getRepositoryAlias().'.name', $repository->getRepositoryAlias().'.url as slug');
        } elseif ($entityClass == 'FaEntityBundle:Category') {
            $qb->select($repository->getRepositoryAlias().'.id', $repository->getRepositoryAlias().'.name', $repository->getRepositoryAlias().'.full_slug as slug', $repository->getRepositoryAlias().'.lvl as lvl');
        } elseif ($entityClass == 'FaEntityBundle:Entity') {
            $qb->select($repository->getRepositoryAlias().'.id', $repository->getRepositoryAlias().'.name', $repository->getRepositoryAlias().'.slug as slug');
        } else {
            $qb->select($repository->getRepositoryAlias().'.id', $repository->getRepositoryAlias().'.name');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $results = $qb->getQuery()->getResult();

        //echo count($results);
        //exit;

        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        if (count($results) > 0) {
            $culture = CommonManager::getCurrentCulture($this->getContainer());

            foreach ($results as $result) {
                $cacheKey = 'EntityCacheManager'.'|'.'getEntityNameById'.'|'.$entityClass.'_'.$result['id'];
                CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $result['name']);
                if (isset($result['slug'])) {
                    $cacheKey = 'EntityCacheManager'.'|'.'getEntitySlugById'.'|'.$entityClass.'_'.$result['id'];
                    CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $result['slug']);
                } if ($entityClass == 'FaEntityBundle:Category') {
                    $categoryPathArray = $repository->getCategoryPathArrayById1($result['id']);
                    $cacheKey          = 'category'.'|'.'getCategoryPathArrayById'.'|'.$result['id'].'__'.$culture;
                    CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $categoryPathArray);

                    // store category lvl
                    $cacheKey = 'EntityCacheManager'.'|'.'getEntityLvlById'.'|'.$entityClass.'_'.$result['id'];
                    CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $result['lvl']);

                    // Generate cache for paa field
                    $entityManager->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($result['id'], $this->getContainer());

                    // Get childeren from parent
                    $repository->getChildrenKeyValueArrayByParentId($result['id'], $this->getContainer());

                    // Get leaf level category
                    $repository->getNestedLeafChildrenIdsByCategoryId($result['id'], $this->getContainer());

                    // Get searchable dimension
                    $entityManager->getRepository('FaEntityBundle:CategoryDimension')->getSearchableDimesionsArrayByCategoryId($result['id'], $this->getContainer());

                    // Banner cache
                    $repository->getCategoryPathDetailArrayById($result['id'], false, $this->getContainer());

                    // category object
                    $repository->getCategoryByFullSlug($result['slug'], $this->getContainer(), true);

                    // package upsell
                    $entityManager->getRepository('FaPromotionBundle:Package')->getShopPackageProfileExposureUpsellByCategory($result['id'], $this->getContainer());

                    // update category dimensions
                    $entityManager->getRepository('FaEntityBundle:CategoryDimension')->getIndexableDimesionsArrayByCategoryId($result['id'], $this->getContainer());
                    $entityManager->getRepository('FaEntityBundle:CategoryDimension')->getDimesionsByCategoryId($result['id'], $this->getContainer());

                    // Paa fields rules for load fields on posting and editing.
                    $entityManager->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryId($result['id'], $this->getContainer(), null, 'both');
                    $entityManager->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryId($result['id'], $this->getContainer(), 2);
                    $entityManager->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesArrayByCategoryId($result['id'], $this->getContainer(), 4);

                    // Expiration rule for ads
                    $entityManager->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($result['id'], $this->getContainer());
                }
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update entity cache.
     *
     * @param array   $searchParam    Search parameters.
     * @param string  $action         Whether to generate or remove.
     * @param string  $entityClass    String entity classes.
     * @param object  $input          Input object.
     * @param object  $output         Output object.
     */
    protected function updateEntityCache($searchParam, $action, $entityClass, $input, $output)
    {
        $count     = $this->getCount($searchParam, $entityClass);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:entity '.' '.$input->getArgument('action').' '.$input->getArgument('class').' '.$commandOptions;
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
     * Get query builder.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getQueryBuilder($searchParam, $class)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $repository    = $entityManager->getRepository($class);

        $data                  = array();
        $data['query_filters'] = $searchParam;

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($repository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder count.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCount($searchParam, $class)
    {
        $qb = $this->getQueryBuilder($searchParam, $class);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
