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

/**
 * This command is used to generate location landing page site map.
 *
 * php app/console fa:generate:location:landing:page:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateLocationLandingPageSiteMapCommand extends SiteMap
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
        ->setName('fa:generate:location:landing:page:sitemap')
        ->setDescription("Generate location landing page sitemap.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to generate location landing page sitemap.

Actions:
- Generate general location landing page sitemap.

Command:
 - php app/console fa:generate:location:landing:page:sitemap

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

        if (!isset($offset)) {
            $start_time = time();
            $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        }

        if (isset($offset)) {
            $this->generateLocationLandingPageWithOffset($input, $output);
        } else {
            $fhandle = $this->generateUrlsetHeaderXml('landing_pages_location', $output);
            gzclose($fhandle);
            $this->generateLocationLandingPage($input, $output);
        }

        if (!isset($offset)) {
            $file = $this->siteMapXmlPath.'/landing_pages_location.xml.gz';
            $fhandle = gzopen($file, 'ab');
            gzwrite($fhandle, '</urlset>');
            gzclose($fhandle);

            $this->splitXml('landing_pages_location', $output);

            $output->writeln('File landing_pages_location.xml.gz generated successfully.');
            $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
            $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
        }
    }

    /**
     * Generate location landing page.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function generateLocationLandingPage($input, $output)
    {
        $count  = count($this->getCountyTownArray($input, $output));

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:generate:location:landing:page:sitemap '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Generate location landing page with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function generateLocationLandingPageWithOffset($input, $output)
    {
        $offset = $input->getOption('offset');
        $file = $this->siteMapXmlPath.'/landing_pages_location.xml.gz';
        $fhandle = gzopen($file, 'ab');

        if ($fhandle) {
            if ($offset > 0) {
                $offset = $offset + 1;
            }

            $siteMapXml = '';
            $landingPages = $this->entityManager->getRepository('FaContentBundle:SeoTool')->getStaticLandingPages();
            $allLocations = $this->getCountyTownArray($input, $output);
            $locations = array_slice($allLocations, $offset, $this->limit);

            if ($landingPages) {
                foreach ($landingPages as $landingPage) {
                    list($keywords, $searchParams) = $this->handleCustomizedUrl($landingPage->getSourceUrl());
                    $targetCatText = strtok($landingPage->getSourceUrl(), '?');
                    $targetCatText =  substr($targetCatText, 0, strrpos($targetCatText, '/'));
                    $catObj = $this->entityManager->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($targetCatText, $this->getContainer());
                    if ($catObj) {
                        $searchParams['item__category_id'] = $catObj['id'];
                    }

                    // location wise landing page
                    foreach ($locations as $locationId) {
                        $locationSlug = $this->entityManager->getRepository('FaEntityBundle:Location')->getSlugById($locationId);
                        $searchParams['item__location'] = $locationId;
                        $searchParamsCommandLine = array('search' => $searchParams);
                        $searchParamsCommandLine['sort_field'] = 'item__published_at';
                        $searchParamsCommandLine['sort_ord'] = 'desc';
                        $searchParamsCommandLine['page']  = 1;

                        // initialize search filter manager service and prepare filter data for searching
                        $this->getContainer()->get('fa.searchfilters.manager')->init($this->entityManager->getRepository('FaAdBundle:Ad'), $this->entityManager->getClassMetadata('FaAdBundle:Ad')->getTableName(), 'search', $searchParamsCommandLine);
                        $data = $this->getContainer()->get('fa.searchfilters.manager')->getFiltersData();

                        // Active ads
                        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;

                        // ad location filter with distance
                        if (isset($searchParams['item__location']) && $searchParams['item__location']) {
                            $data['query_filters']['item']['location'] = $searchParams['item__location'].'|'. (isset($searchParams['item__distance']) ? $searchParams['item__distance'] : '');
                        }

                        // initialize solr search manager service and fetch data based of above prepared search options
                        $this->getContainer()->get('fa.solrsearch.manager')->init('ad', $keywords, $data, 1, 1, 0, true);
                        $solrResponse = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponse();
                        $resultCount = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);

                        if ($resultCount) {
                            $siteMapXml .= $this->generateUrlTag($this->generateUrl('listing_page', array('location' => $locationSlug, 'page_string' => $targetCatText =  substr($landingPage->getTargetUrl(), 0, strrpos($landingPage->getTargetUrl(), '/')))));
                        }
                    }
                }
                gzwrite($fhandle, $siteMapXml);
            }

            gzclose($fhandle);
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     *
     * @param  array $data
     * @return array
     */
    private function handleCustomizedUrl($source_url)
    {
        $cqparams = array();
        $queryParams = array();
        $source_data = parse_url($source_url);

        if (isset($source_data['query'])) {
            $cqparams = explode('&', $source_data['query']);
        }
        $qa = array();
        $keyword = null;

        foreach ($cqparams as $key => $val) {
            $vparams = explode('=', $val);
            if (count($vparams) == 2) {
                $qa[$vparams[0]] = $vparams[1];
            }
        }

        foreach ($qa as $key => $val) {
            if (preg_match('/^(.*)_id$/', $key) || preg_match('/reg_year|mileage_range|engine_size_range/', $key)) {
                $queryParams[$key] = explode("__", $val);

                if (preg_match('/^(.*)_id$/', $key)) {
                    $queryParams[$key] = array_map('intval', explode("__", $val));
                }
            } else {
                if ($key == 'keywords') {
                    $keyword = $val;
                } else {
                    $queryParams[$key] = $val;
                }
            }
        }

        return array($keyword, $queryParams);
    }

    /**
     * Generate location level county town facet array.
     *
     * @param object  $input      Input object.
     * @param object  $output     Output object.
     */
    protected function getCountyTownArray($input, $output)
    {
        $data = array();
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['facet_fields'] = array('a_l_main_town_id_i' => array('limit' => '5000', 'min_count' => 1),'a_l_domicile_id_txt' => array('limit' => '5000', 'min_count' => 1));

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

        return $countyTownArray;
    }
}
