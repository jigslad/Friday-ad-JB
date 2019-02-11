<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Fa\Bundle\AdBundle\Repository\SearchKeywordCategoryRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerNewsletterTypeRepository;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerInfoRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ReportBundle\Repository\AdEnquiryReportDailyRepository;
use Fa\Bundle\ReportBundle\Repository\AdEnquiryReportRepository;
use Fa\Bundle\ReportBundle\Repository\AdPrintReportDailyRepository;
use Fa\Bundle\ReportBundle\Repository\AdReportDailyRepository;
use Fa\Bundle\ReportBundle\Repository\UserReportCategoryDailyRepository;

/**
 * This command is used to update jobs category.
 *
 * php app/console fa:update:job-categories-entity
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateJobCategoriesEntityCommand extends ContainerAwareCommand
{
    /**
     * Default entity manager
     *
     * @var object
     */
    private $entityManager;

    /**
     * Default entity manager
     *
     * @var object
     */
    private $mappingArray;

    /**
     * History db name
     *
     * @var object
     */
    private $historyDbName;

    /**
     * History entity manager
     *
     * @var object
     */
    private $historyEntityManager;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:job-categories-entity')
        ->setDescription("Update job categories")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "256M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Offset of the query', 'ad')
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update job categories entity.

Command:
 - php app/console fa:update:job-categories-entity --type="ad"
EOF
        );
        ;
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
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->historyEntityManager = $this->getContainer()->get('doctrine')->getManager('history');
        $this->historyDbName        = $this->getContainer()->getParameter('database_name_history');
        $reader = new \EasyCSV\Reader(__DIR__."/job_mapping.csv");
        $reader->setDelimiter(';');
        $this->mappingArray = array();
        while ($row = $reader->getRow()) {
            $this->mappingArray[$row['old_id']] = $row['new_id'];
        }

        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateJobCategoriesEntityOffset($input, $output);
        } else {
            $this->updateJobCategoriesEntity($input, $output);
        }
    }

    /**
     * Update entity with offset.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateJobCategoriesEntityOffset($input, $output)
    {
        $type = $input->getOption('type');
        $step        = 100;
        $offset      = 0;

        $entities = $this->getEntityResult($type, $offset, $step);

        foreach ($entities as $entity) {
            if ($type == 'ad') {
                $oldCategoryId = $entity->getCategory()->getId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update ad set old_cat_id = '.$oldCategoryId.' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->entityManager);

                    $updateSql = 'update ad set category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->entityManager);
                    echo '.';
                }
            } elseif ($type == 'seo_tool') {
                $oldCategoryId = $entity->getCategory()->getId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $entity->setCategory($this->entityManager->getReference('FaEntityBundle:Category', $this->mappingArray[$oldCategoryId]));
                    $this->entityManager->persist($entity);
                    $this->entityManager->flush($entity);
                    echo '.';
                }
            } elseif ($type == 'search_keyword_category' || $type == 'dotmailer_newsletter_type') {
                $oldCategoryId = $entity->getCategoryId();
                if (isset($this->mappingArray[$oldCategoryId]) && $this->mappingArray[$oldCategoryId]) {
                    $entity->setCategoryId($this->mappingArray[$oldCategoryId]);
                    $this->entityManager->persist($entity);
                    $this->entityManager->flush($entity);
                    echo '.';
                }
            } elseif ($type == 'dotmailer_info_paa_cat_id') {
                $oldCategoryId = $entity->getPaaCategoryId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update dotmailer_info set paa_category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->entityManager);
                    echo '.';
                }
            } elseif ($type == 'dotmailer_info_enquiry_cat_id') {
                $oldCategoryId = $entity->getEnquiryCategoryId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update dotmailer_info set enquiry_category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->entityManager);
                    echo '.';
                }
            } elseif ($type == 'ad_enquiry_report') {
                $oldCategoryId = $entity->getCategoryId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update '.$this->historyDbName.'.ad_enquiry_report set category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->historyEntityManager);
                    echo '.';
                }
            } elseif ($type == 'ad_print_report_daily') {
                $oldCategoryId = $entity->getCategoryId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update '.$this->historyDbName.'.ad_print_report_daily set category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->historyEntityManager);
                    echo '.';
                }
            } elseif ($type == 'ad_report_daily') {
                $oldCategoryId = $entity->getCategoryId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update '.$this->historyDbName.'.ad_report_daily set category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->historyEntityManager);
                    echo '.';
                }
            } elseif ($type == 'user_report_category_daily') {
                $oldCategoryId = $entity->getCategoryId();
                if (isset($this->mappingArray[$oldCategoryId])) {
                    $updateSql = 'update '.$this->historyDbName.'.user_report_category_daily set category_id = '.$this->mappingArray[$oldCategoryId].' where id ='.$entity->getId();
                    $this->executeRawQuery($updateSql, $this->historyEntityManager);
                    echo '.';
                }
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update entity.
     *
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateJobCategoriesEntity($input, $output)
    {
        $type = $input->getOption('type');
        $count     = $this->getEntityCount($type);
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('total '.$type.' : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:job-categories-entity '.$commandOptions;
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
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder()
    {
        $adRepository  = $this->entityManager->getRepository('FaAdBundle:Ad');

        $data                  = array();
        $data['static_filters'] = AdRepository::ALIAS.'.category IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($adRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for seo tool.
     *
     * @return Doctrine_Query Object.
     */
    protected function getSeoToolQueryBuilder()
    {
        $seoToolRepository  = $this->entityManager->getRepository('FaContentBundle:SeoTool');

        $data                  = array();
        $data['static_filters'] = SeoToolRepository::ALIAS.'.category IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getSearchKeywordCategoryQueryBuilder()
    {
        $seoToolRepository  = $this->entityManager->getRepository('FaAdBundle:SearchKeywordCategory');

        $data                  = array();
        $data['static_filters'] = SearchKeywordCategoryRepository::ALIAS.'.category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerNewsletterTypeQueryBuilder()
    {
        $seoToolRepository  = $this->entityManager->getRepository('FaDotMailerBundle:DotmailerNewsletterType');

        $data                  = array();
        $data['static_filters'] = DotmailerNewsletterTypeRepository::ALIAS.'.category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerInfoPaaCatIdQueryBuilder()
    {
        $seoToolRepository  = $this->entityManager->getRepository('FaDotMailerBundle:DotmailerInfo');

        $data                  = array();
        $data['static_filters'] = DotmailerInfoRepository::ALIAS.'.paa_category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getDotmailerInfoEnquiryCatIdQueryBuilder()
    {
        $seoToolRepository  = $this->entityManager->getRepository('FaDotMailerBundle:DotmailerInfo');

        $data                  = array();
        $data['static_filters'] = DotmailerInfoRepository::ALIAS.'.enquiry_category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdEnquiryReportQueryBuilder()
    {
        $seoToolRepository  = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdEnquiryReport');

        $data                  = array();
        $data['static_filters'] = AdEnquiryReportRepository::ALIAS.'.category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdPrintReportDailyQueryBuilder()
    {
        $seoToolRepository  = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdPrintReportDaily');

        $data                  = array();
        $data['static_filters'] = AdPrintReportDailyRepository::ALIAS.'.category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdReportDailyQueryBuilder()
    {
        $seoToolRepository  = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:AdReportDaily');

        $data                  = array();
        $data['static_filters'] = AdReportDailyRepository::ALIAS.'.category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for search keyword category.
     *
     * @return Doctrine_Query Object.
     */
    protected function getUserReportCategoryDailyQueryBuilder()
    {
        $seoToolRepository  = CommonManager::getHistoryRepository($this->getContainer(), 'FaReportBundle:UserReportCategoryDaily');

        $data                  = array();
        $data['static_filters'] = UserReportCategoryDailyRepository::ALIAS.'.category_id IN ('.implode(',', array_keys($this->mappingArray)).')';

        $searchManager = $this->getContainer()->get('fa.sqlsearch.manager');
        $searchManager->init($seoToolRepository, $data);

        return $searchManager->getQueryBuilder();
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getEntityResult($type, $offset, $step)
    {
        if ($type == 'ad') {
            $qb = $this->getAdQueryBuilder();
        } elseif ($type == 'seo_tool') {
            $qb = $this->getSeoToolQueryBuilder();
        } elseif ($type == 'search_keyword_category') {
            $qb = $this->getSearchKeywordCategoryQueryBuilder();
        } elseif ($type == 'dotmailer_newsletter_type') {
            $qb = $this->getDotmailerNewsletterTypeQueryBuilder();
        } elseif ($type == 'dotmailer_info_paa_cat_id') {
            $qb = $this->getDotmailerInfoPaaCatIdQueryBuilder();
        } elseif ($type == 'dotmailer_info_enquiry_cat_id') {
            $qb = $this->getDotmailerInfoEnquiryCatIdQueryBuilder();
        } elseif ($type == 'ad_enquiry_report') {
            $qb = $this->getAdEnquiryReportQueryBuilder();
        } elseif ($type == 'ad_print_report_daily') {
            $qb = $this->getAdPrintReportDailyQueryBuilder();
        } elseif ($type == 'ad_report_daily') {
            $qb = $this->getAdReportDailyQueryBuilder();
        } elseif ($type == 'user_report_category_daily') {
            $qb = $this->getUserReportCategoryDailyQueryBuilder();
        }


        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);
        return $qb->getQuery()->getResult();
    }

    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getEntityCount($type)
    {
        if ($type == 'ad') {
            $qb = $this->getAdQueryBuilder();
        } elseif ($type == 'seo_tool') {
            $qb = $this->getSeoToolQueryBuilder();
        } elseif ($type == 'search_keyword_category') {
            $qb = $this->getSearchKeywordCategoryQueryBuilder();
        } elseif ($type == 'dotmailer_newsletter_type') {
            $qb = $this->getDotmailerNewsletterTypeQueryBuilder();
        } elseif ($type == 'dotmailer_info_paa_cat_id') {
            $qb = $this->getDotmailerInfoPaaCatIdQueryBuilder();
        } elseif ($type == 'dotmailer_info_enquiry_cat_id') {
            $qb = $this->getDotmailerInfoEnquiryCatIdQueryBuilder();
        } elseif ($type == 'ad_enquiry_report') {
            $qb = $this->getAdEnquiryReportQueryBuilder();
        } elseif ($type == 'ad_print_report_daily') {
            $qb = $this->getAdPrintReportDailyQueryBuilder();
        } elseif ($type == 'ad_report_daily') {
            $qb = $this->getAdReportDailyQueryBuilder();
        } elseif ($type == 'user_report_category_daily') {
            $qb = $this->getUserReportCategoryDailyQueryBuilder();
        }

        $qb->select('COUNT('.$qb->getRootAlias().'.id)');
        return $qb->getQuery()->getSingleScalarResult();
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
}
