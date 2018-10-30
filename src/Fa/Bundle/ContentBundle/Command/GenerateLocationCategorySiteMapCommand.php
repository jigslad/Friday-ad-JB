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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This command is used to generate location level categopry site map.
 *
 * php app/console fa:generate:location:category:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateLocationCategorySiteMapCommand extends SiteMap
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 100;

    /**
     * Priority in site map
     *
     * @var string
     */
    protected $priority = '0.8';
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:location:category:sitemap')
        ->setDescription("Generate location level categories with filter sitemap.")
        ->addOption('root_category_id', null, InputOption::VALUE_REQUIRED, 'Root level category')
        ->addOption('file_name', null, InputOption::VALUE_REQUIRED, 'File name of site map')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to generate location level categories with filter sitemap.

Actions:
- Generate location level categopry sitemap.

Command:
 - php app/console fa:generate:location:category:sitemap --root_category_id=2 --file_name=sitemap_forsale_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=444 --file_name=sitemap_motors_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=725 --file_name=sitemap_animals_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=500 --file_name=sitemap_jobs_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=585 --file_name=sitemap_services_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=678 --file_name=sitemap_property_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=783 --file_name=sitemap_community_locations
 - php app/console fa:generate:location:category:sitemap --root_category_id=3411 --file_name=sitemap_adult_locations

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
        $this->initializeSiteMapParameters();
        $offset = $input->getOption('offset');
        $fileName = $input->getOption('file_name');

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        // insert ads statistics.
        if (isset($offset)) {
            $this->generateLocationLevelCategoriesLinksWithOffset($input, $output);
        } else {
            $fhandle = $this->generateUrlsetHeaderXml($fileName, $output);
            gzclose($fhandle);
            $this->generateLocationLevelCategoriesLinks($input, $output);
        }

        if (!isset($offset)) {
            $file = $this->siteMapXmlPath.'/'.$fileName.'.xml.gz';
            $fhandle = gzopen($file, 'ab');
            gzwrite($fhandle, '</urlset>');
            gzclose($fhandle);

            $this->splitXml($fileName, $output);

            $output->writeln('File "'.$fileName.'" generated successfully.');
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Generate location level categories links.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function generateLocationLevelCategoriesLinks($input, $output)
    {
        $count  = count($this->getUkLevelCategories($input, $output));

        for ($i = 0; $i < $count;) {
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:generate:location:category:sitemap '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Generate location level categories facet array.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function getUkLevelCategories($input, $output)
    {
        $rootCategoryId = $input->getOption('root_category_id');
        $data                 = array();
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['category_id'] = $rootCategoryId;
        $data['facet_fields'] = array('a_category_id_i' => array('limit' => '5000', 'min_count' => 1),'a_parent_category_lvl_1_id_i' => array('limit' => '5000', 'min_count' => 1), 'a_parent_category_lvl_2_id_i' => array('limit' => '5000', 'min_count' => 1), 'a_parent_category_lvl_3_id_i' => array('limit' => '5000', 'min_count' => 1), 'a_parent_category_lvl_4_id_i' => array('limit' => '5000', 'min_count' => 1), 'a_parent_category_lvl_5_id_i' => array('limit' => '5000', 'min_count' => 1), 'a_parent_category_lvl_6_id_i' => array('limit' => '5000', 'min_count' => 1));

        // initialize solr search manager service and fetch data based of above prepared search options
        $this->getContainer()->get('fa.solrsearch.manager')->init('ad', '', $data);
        $solrResponse = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponse();

        // fetch result set from solr
        $result = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);
        $categoryCountArray = array();


        if (isset($result['a_parent_category_lvl_1_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_parent_category_lvl_1_id_i'])));
        }
        if (isset($result['a_parent_category_lvl_2_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_parent_category_lvl_2_id_i'])));
        }
        if (isset($result['a_parent_category_lvl_3_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_parent_category_lvl_3_id_i'])));
        }
        if (isset($result['a_parent_category_lvl_4_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_parent_category_lvl_4_id_i'])));
        }
        if (isset($result['a_parent_category_lvl_5_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_parent_category_lvl_5_id_i'])));
        }
        if (isset($result['a_parent_category_lvl_6_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_parent_category_lvl_6_id_i'])));
        }
        if (isset($result['a_category_id_i'])) {
            $categoryCountArray = array_merge($categoryCountArray, array_keys(get_object_vars($result['a_category_id_i'])));
        }

        return $categoryCountArray;
    }

    /**
     * Generate location level county town facet array.
     *
     * @param object  $input      Input object.
     * @param object  $output     Output object.
     * @param integer $categoryId Category id.
     */
    protected function getCountyTownArrayForCategoryId($input, $output, $categoryId = null)
    {
        $data = array();
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        if ($categoryId) {
            $data['query_filters']['item']['category_id'] = $categoryId;
        }
        $data['facet_fields'] = array('a_l_main_town_id_i' => array('limit' => '5000', 'min_count' => 1),'a_l_domicile_id_txt' => array('limit' => '5000', 'min_count' => 1),'a_l_area_id_txt' => array('limit' => '5000', 'min_count' => 1));
        // initialize solr search manager service and fetch data based of above prepared search options
        $this->getContainer()->get('fa.solrsearch.manager')->init('ad', '', $data);
        $solrResponse = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponse();

        // fetch result set from solr
        $result = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseFacetFields($solrResponse);
        $countyTownArray = array();

        if (isset($result['a_l_domicile_id_txt'])) {
            $countyTownArray = array_merge($countyTownArray, array_keys(get_object_vars($result['a_l_domicile_id_txt'])));
        }
        if (isset($result['a_l_main_town_id_i'])) {
            $countyTownArray = array_merge($countyTownArray, array_keys(get_object_vars($result['a_l_main_town_id_i'])));
        }
        if (isset($result['a_l_area_id_txt'])) {
            $countyTownArray = array_merge($countyTownArray, array_keys(get_object_vars($result['a_l_area_id_txt'])));
        }
        
        return $countyTownArray;
    }

    /**
     * Generate location level categories links with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function generateLocationLevelCategoriesLinksWithOffset($input, $output)
    {
        $fileName = $input->getOption('file_name');
        $rootCategoryId = $input->getOption('root_category_id');
        $offset = $input->getOption('offset');
        $routingManager = $this->getContainer()->get('fa_ad.manager.ad_routing');
        $file = $this->siteMapXmlPath.'/'.$fileName.'.xml.gz';
        $fhandle = gzopen($file, 'ab');

        if ($offset > 0) {
            $offset = $offset + 1;
        } else {
            if (in_array($rootCategoryId, array(CategoryRepository::FOR_SALE_ID, CategoryRepository::MOTORS_ID, CategoryRepository::ANIMALS_ID, CategoryRepository::PROPERTY_ID, CategoryRepository::JOBS_ID))) {
                if ($fhandle) {
                    $rootCatObj = $this->entityManager->getRepository('FaEntityBundle:Category')->find($rootCategoryId);
                    if ($rootCatObj) {
                        $siteMapXml = '';
                        $locations = $this->getCountyTownArrayForCategoryId($input, $output);
                        // location wise landing page
                        foreach ($locations as $locationId) {
                            $locationSlug = $this->entityManager->getRepository('FaEntityBundle:Location')->getSlugById($locationId);
                            $siteMapXml .= $this->generateUrlTag($this->generateUrl('landing_page_category_location', array('category_string' => $rootCatObj->getFullSlug(), 'location' => $locationSlug)));
                        }
                        gzwrite($fhandle, $siteMapXml);
                    }
                }
            }
        }

        $excludeCategoryIds = array();

        if ($rootCategoryId == CategoryRepository::MOTORS_ID) {
            $excludeCategoryIds = $this->entityManager->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId(CategoryRepository::CARS_ID, $this->getContainer());
            $excludeCategoryIds = array_merge($excludeCategoryIds, $this->entityManager->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId(CategoryRepository::COMMERCIALVEHICLES_ID, $this->getContainer()));
            $carKey = array_search(CategoryRepository::CARS_ID, $excludeCategoryIds);
            if ($carKey !== false) {
                unset($excludeCategoryIds[$carKey]);
            }
            $commercialVehicleKey = array_search(CategoryRepository::COMMERCIALVEHICLES_ID, $excludeCategoryIds);
            if ($commercialVehicleKey !== false) {
                unset($excludeCategoryIds[$commercialVehicleKey]);
            }
        }


        $allCategories = $this->getUkLevelCategories($input, $output, false);
        $categories = array_slice($allCategories, $offset, $this->limit);

        $siteMapXml = '';
        foreach ($categories as $categoryId) {
            if (in_array($categoryId, $excludeCategoryIds)) {
                continue;
            }
            // categorywise url
            $categoryFullSlug = $this->entityManager->getRepository('FaEntityBundle:Category')->getFullSlugById($categoryId);
            $locations = $this->getCountyTownArrayForCategoryId($input, $output, $categoryId);
            //by pass motors category.
            if ($categoryFullSlug == 'motors') {
                continue;
            }
            foreach ($locations as $locationId) {
                $locationSlug = $this->entityManager->getRepository('FaEntityBundle:Location')->getSlugById($locationId);
                if ($categoryFullSlug && $locationSlug) {
                    try {
                        $categoryUrl = $routingManager->getCategoryUrl($locationSlug, $categoryFullSlug);
                        if (strpos($categoryUrl, 'other') === false) {
                            $siteMapXml .= $this->generateUrlTag($categoryUrl);
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        gzwrite($fhandle, $siteMapXml);
        gzclose($fhandle);
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }
}
