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
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;

/**
 * This command is used to generate seo rule cache.
 *
 * php app/console fa:update:seo:rule:cache generate adp
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateSeoRuleCacheCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:seo:rule:cache')
        ->setDescription("Update or Remove seo rule cache")
        ->addArgument('action', InputArgument::REQUIRED, 'generate or remove')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addArgument('page', InputArgument::REQUIRED, 'page type')
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to generate and remove entity cache.

Actions:
- Generate cache.

Command:
 - php app/console fa:update:seo:rule:cache generate adp
 - php app/console fa:update:seo:rule:cache generate aia
 - php app/console fa:update:seo:rule:cache generate hp
 - php app/console fa:update:seo:rule:cache generate alp
 - php app/console fa:update:seo:rule:cache remove adp
 - php app/console fa:update:seo:rule:cache remove aia
 - php app/console fa:update:seo:rule:cache remove hp
 - php app/console fa:update:seo:rule:cache remove alp

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
            'page' => $page,
            'status' => 1,
        );

        if ($action == 'generate') {
            if (isset($offset)) {
                $this->updateSeoRuleCacheWithOffset($searchParam, $action, $page, $input, $output);
                $output->writeln('Cache generate for: '.$page);
            } else {
                $this->updateSeoRuleCache($searchParam, $action, $page, $input, $output);
            }
        } elseif ($action == 'remove') {
            //remove cache
            CommonManager::removeCachePattern($this->getContainer(), $this->getSeoToolTableName().'|getSeoRulesKeyValueArray'.'|'.$page.'*');
            if (in_array($page, array(SeoToolRepository::ADVERT_DETAIL_PAGE, SeoToolRepository::ADVERT_IMG_ALT))) {
                CommonManager::removeCachePattern($this->getContainer(), $this->getSeoToolTableName().'|getSeoPageRuleDetailForSolrResult'.'|*_'.$page.'*');
            }
            if ($page == SeoToolRepository::ADVERT_LIST_PAGE) {
                CommonManager::removeCachePattern($this->getContainer(), $this->getSeoToolTableName().'|getSeoPageRuleDetailForListResult'.'|*_'.$page.'*');
            }
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
    protected function updateSeoRuleCacheWithOffset($searchParam, $action, $page, $input, $output)
    {
        $qb     = $this->getQueryBuilder($searchParam);
        $step   = 1000;
        $offset = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $seoRules = $qb->getQuery()->getResult();

        if (count($seoRules) > 0) {
            $culture      = CommonManager::getCurrentCulture($this->getContainer());
            $cacheKey     = $this->getSeoToolTableName().'|getSeoRulesKeyValueArray|'.$page.'_'.$culture;
            $seoRuleArray = CommonManager::getCacheVersion($this->getContainer(), $cacheKey);

            foreach ($seoRules as $seoRule) {
                $seoRuleArray[$page.'_'.($seoRule->getCategory() ? $seoRule->getCategory()->getId() : 'global')] = array(
                    'h1_tag' => $seoRule->getH1Tag(),
                    'meta_description' => $seoRule->getMetaDescription(),
                    'meta_keywords' => $seoRule->getMetaKeywords(),
                    'page_title' => $seoRule->getPageTitle(),
                    'no_index' => $seoRule->getNoIndex(),
                    'no_follow' => $seoRule->getNoFollow(),
                    'image_alt' => $seoRule->getImageAlt(),
                );
            }
            CommonManager::setCacheVersion($this->getContainer(), $cacheKey, $seoRuleArray);
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
    protected function updateSeoRuleCache($searchParam, $action, $page, $input, $output)
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:seo:rule:cache '.' '.$input->getArgument('action').' '.$input->getArgument('page').' '.$commandOptions;
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
        $seoToolRepository = $entityManager->getRepository('FaContentBundle:SeoTool');

        $qb = $seoToolRepository->createQueryBuilder(SeoToolRepository::ALIAS);

        if (isset($searchParam['page']) && $searchParam['page']) {
            $qb->andWhere(SeoToolRepository::ALIAS.'.page = :page')
            ->setParameter('page', $searchParam['page']);
        }

        if (isset($searchParam['status'])) {
            $qb->andWhere(SeoToolRepository::ALIAS.'.status = :status')
            ->setParameter('status', $searchParam['status']);
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
        $qb->select('COUNT('.SeoToolRepository::ALIAS.'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get seo tool table name.
     */
    private function getSeoToolTableName()
    {
        $entityManager  = $this->getContainer()->get('doctrine')->getManager();

        return $entityManager->getClassMetadata('FaContentBundle:SeoTool')->getTableName();
    }
}
