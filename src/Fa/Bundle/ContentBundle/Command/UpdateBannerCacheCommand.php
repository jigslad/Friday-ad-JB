<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Fa\Bundle\ContentBundle\Repository\BannerZoneRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This command is used to generate banner cache.
 *
 * php app/console fa:update:banner:cache generate 1
 * php app/console fa:update:banner:cache remove 2
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateBannerCacheCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:banner:cache')
        ->setDescription("Update or Remove banner cache")
        ->addArgument('action', InputArgument::REQUIRED, 'generate or remove')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "768M")
        ->addArgument('page', InputArgument::REQUIRED, 'page')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to generate and remove entity cache.

Actions:
- Generate cache.

Command:
 - php app/console fa:update:banner:cache generate 1
 - php app/console fa:update:banner:cache generate 2
 - php app/console fa:update:banner:cache remove 1
 - php app/console fa:update:banner:cache remove 2

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
        $page   = $input->getArgument('page');
        $offset = $input->getOption('offset');

        $searchParam = array(
            'page' => $page
        );

        if ($action == 'generate') {
            if (isset($offset)) {
                $this->updateBannerCacheWithOffset($searchParam, $action, $page, $input, $output);
                $output->writeln('Cache generate for: '.$page);
            } else {
                $this->updateBannerCache($searchParam, $action, $page, $input, $output);
            }
        } elseif ($action == 'remove') {
            //remove cache
            CommonManager::removeCachePattern($this->getContainer(), $this->getBannerTableName().'|getBannersArrayByPage'.'|'.$page.'*');
            $output->writeln('Cache removed for: '.$page);
        }
    }

    /**
     * Update entity cache with offset.
     *
     * @param array   $searchParam    Search parameters.
     * @param string  $action         Whether to generate or remove.
     * @param string  $page    String entity classes.
     * @param object  $input          Input object.
     * @param object  $output         Output object.
     */
    protected function updateBannerCacheWithOffset($searchParam, $action, $page, $input, $output)
    {
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();
        $bannerRepository = $entityManager->getRepository('FaContentBundle:Banner');
        $categoryRepository = $entityManager->getRepository('FaEntityBundle:Category');

        //get general rule for page
        $bannerPageGeneralArray = $bannerRepository->getBaseQueryBuilder()
        ->select(BannerRepository::ALIAS.'.id as banner_id,'.BannerRepository::ALIAS.'.code,'.BannerZoneRepository::ALIAS.'.id As zone_id,'.BannerZoneRepository::ALIAS.'.max_width,'.BannerZoneRepository::ALIAS.'.max_height,'.BannerZoneRepository::ALIAS.'.is_desktop,'.BannerZoneRepository::ALIAS.'.is_tablet,'.BannerZoneRepository::ALIAS.'.is_mobile,'.BannerPageRepository::ALIAS.'.id as page_id')
        ->innerJoin(BannerRepository::ALIAS.'.banner_pages', BannerPageRepository::ALIAS)
        ->innerJoin(BannerRepository::ALIAS.'.banner_zone', BannerZoneRepository::ALIAS)
        ->andWhere(BannerPageRepository::ALIAS.'.id = (:pageId)')
        ->andWhere(BannerRepository::ALIAS.'.category IS NULL')
        ->setParameter('pageId', $searchParam['page'])
        ->getQuery()
        ->getResult();

        $qb     = $this->getQueryBuilder($searchParam);
        $qb->select(BannerRepository::ALIAS.'.id as banner_id,'.BannerRepository::ALIAS.'.code,'.BannerZoneRepository::ALIAS.'.id As zone_id,'.BannerZoneRepository::ALIAS.'.max_width,'.BannerZoneRepository::ALIAS.'.max_height,'.BannerZoneRepository::ALIAS.'.is_desktop,'.BannerZoneRepository::ALIAS.'.is_tablet,'.BannerZoneRepository::ALIAS.'.is_mobile,'.BannerPageRepository::ALIAS.'.id as page_id', 'IDENTITY('.BannerRepository::ALIAS.'.category) as category_id');
        $step   = 1000;
        $offset = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $bannersArray = $qb->getQuery()->getResult();
        $bannersFinalArray = array();
        $culture  = CommonManager::getCurrentCulture($this->getContainer());

        if (count($bannersArray) > 0) {
            if ($bannersArray && count($bannersArray) > 0) {
                foreach ($bannersArray as $bannersTmpArray) {
                    $categoryId = (isset($bannersTmpArray['category_id']) ? $bannersTmpArray['category_id'] : null);
                    $cacheKey = $this->getBannerTableName().'|getBannersArrayByPage|'.$page.'_'.$categoryId.'_'.$culture;
                    $categoryBannerArray = CommonManager::getCacheVersion($this->getContainer(), $cacheKey);
                    if (!isset($categoryBannerArray[$bannersTmpArray['zone_id']])) {
                        $categoryBannerArray[$bannersTmpArray['zone_id']] = $bannersTmpArray;
                        CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $categoryBannerArray);
                    }
                }

                foreach ($bannersArray as $bannersTmpArray) {
                    $categoryId = (isset($bannersTmpArray['category_id']) ? $bannersTmpArray['category_id'] : null);
                    if ($categoryId) {
                        if (in_array($page, array(BannerPageRepository::PAGE_SEARCH_RESULTS, BannerPageRepository::PAGE_AD_DETAILS))) {
                            $childrenCategories = $categoryRepository->getNestedChildrenIdsByCategoryId($categoryId, $this->getContainer(), true);
                            foreach ($childrenCategories as $childrenCategoryId) {
                                $cacheKey = $this->getBannerTableName().'|getBannersArrayByPage|'.$page.'_'.$childrenCategoryId['id'].'_'.$culture;
                                $childrenCategoryBannerArray = CommonManager::getCacheVersion($this->getContainer(), $cacheKey);
                                if (!isset($childrenCategoryBannerArray[$bannersTmpArray['zone_id']])) {
                                    $childrenCategoryBannerArray[$bannersTmpArray['zone_id']] = $bannersTmpArray;
                                    CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $childrenCategoryBannerArray);
                                } elseif (isset($childrenCategoryBannerArray[$bannersTmpArray['zone_id']])) {
                                    if (isset($childrenCategoryBannerArray[$bannersTmpArray['zone_id']]['category_id']) && $childrenCategoryBannerArray[$bannersTmpArray['zone_id']]['category_id']) {
                                        $bannerCategoryLevel  = $this->getContainer()->get('fa.entity.cache.manager')->getEntityLvlById('FaEntityBundle:Category', $childrenCategoryBannerArray[$bannersTmpArray['zone_id']]['category_id']);
                                        if ($childrenCategoryId['level'] > $bannerCategoryLevel) {
                                            $childrenCategoryBannerArray[$bannersTmpArray['zone_id']] = $bannersTmpArray;
                                            CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $childrenCategoryBannerArray);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        //set general rule for other categories for search result & ad detail page.
        if (in_array($page, array(BannerPageRepository::PAGE_SEARCH_RESULTS, BannerPageRepository::PAGE_AD_DETAILS))) {
            $childrenCategories = $categoryRepository->getNestedChildrenIdsByCategoryId(1, $this->getContainer(), true);
            foreach ($childrenCategories as $childrenCategoryId) {
                $cacheKey = $this->getBannerTableName().'|getBannersArrayByPage|'.$page.'_'.$childrenCategoryId['id'].'_'.$culture;
                $childrenCategoryBannerArray = CommonManager::getCacheVersion($this->getContainer(), $cacheKey);

                if (count($bannerPageGeneralArray)) {
                    foreach ($bannerPageGeneralArray as $bannerPageGeneral) {
                        if (!isset($childrenCategoryBannerArray[$bannerPageGeneral['zone_id']])) {
                            $childrenCategoryBannerArray[$bannerPageGeneral['zone_id']] = $bannerPageGeneral;
                            CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $childrenCategoryBannerArray);
                        }
                    }
                } elseif (!$childrenCategoryBannerArray) {
                    CommonManager::setCacheVersion($this->getContainer(), $cacheKey, array());
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
     * @param string  $page    String entity classes.
     * @param object  $input          Input object.
     * @param object  $output         Output object.
     */
    protected function updateBannerCache($searchParam, $action, $page, $input, $output)
    {
        $count     = $this->getCount($searchParam, $page);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:banner:cache '.' '.$input->getArgument('action').' '.$input->getArgument('page').' '.$commandOptions;
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
    protected function getQueryBuilder($searchParam)
    {
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();
        $bannerRepository = $entityManager->getRepository('FaContentBundle:Banner');

        $qb = $bannerRepository->createQueryBuilder(BannerRepository::ALIAS)
            ->innerJoin(BannerRepository::ALIAS.'.banner_pages', BannerPageRepository::ALIAS)
            ->innerJoin(BannerRepository::ALIAS.'.banner_zone', BannerZoneRepository::ALIAS)
            ->andWhere(BannerRepository::ALIAS.'.category IS NOT NULL');

        if (isset($searchParam['page']) && $searchParam['page']) {
            $qb->andWhere(BannerPageRepository::ALIAS.'.id = (:pageId)')
            ->setParameter('pageId', $searchParam['page']);
        }

        return $qb;
    }

    /**
     * Get query builder count.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getCount($searchParam)
    {
        $qb = $this->getQueryBuilder($searchParam);
        $qb->select('COUNT('.BannerRepository::ALIAS.'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get banner table name.
     */
    private function getBannerTableName()
    {
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();

        return $entityManager->getClassMetadata('FaContentBundle:Banner')->getTableName();
    }
}
