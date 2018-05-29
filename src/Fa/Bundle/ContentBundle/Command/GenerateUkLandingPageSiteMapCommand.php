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
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * This command is used to generate uk landing page site map.
 *
 * php app/console fa:generate:uk:landing:page:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateUkLandingPageSiteMapCommand extends SiteMap
{
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
        ->setName('fa:generate:uk:landing:page:sitemap')
        ->setDescription("Generate uk landing page sitemap.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
        ->setHelp(
            <<<EOF
Cron: To be setup to generate uk landing page sitemap.

Actions:
- Generate general uk landing page sitemap.

Command:
 - php app/console fa:generate:uk:landing:page:sitemap

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
        $start_time = time();
        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $start_time), true);
        $this->initializeSiteMapParameters();
        $fhandle = $this->generateUrlsetHeaderXml('landing_pages', $output);
        if ($fhandle) {
            $siteMapXml = '';
            $landingPages = $this->entityManager->getRepository('FaContentBundle:SeoTool')->getStaticLandingPages();
            $locationObj  = $this->entityManager->getRepository('FaEntityBundle:Location')->find(LocationRepository::COUNTY_ID);
            if ($landingPages) {
                foreach ($landingPages as $landingPage) {
                    list($keywords, $searchParams) = $this->handleCustomizedUrl($landingPage->getSourceUrl());
                    $targetCatText = strtok($landingPage->getSourceUrl(), '?');
                    $targetCatText =  substr($targetCatText, 0, strrpos($targetCatText, '/'));
                    $catObj = $this->entityManager->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($targetCatText, $this->getContainer());
                    if ($catObj) {
                        $searchParams['item__category_id'] = $catObj['id'];
                    }

                    $searchParams['item__location'] = LocationRepository::COUNTY_ID;
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
                        $siteMapXml .= $this->generateUrlTag($this->generateUrl('listing_page', array('location' => $locationObj->getUrl(), 'page_string' => $targetCatText =  substr($landingPage->getTargetUrl(), 0, strrpos($landingPage->getTargetUrl(), '/')))));
                    }
                }
                gzwrite($fhandle, $siteMapXml);
            }

            $siteMapXml = '';
            $siteMapXml .= '</urlset>';
            gzwrite($fhandle, $siteMapXml);

            gzclose($fhandle);
            $output->writeln('File "landing_pages.xml.gz" generated successfully.');
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
    }

    /**
     *
     * @param  array $data
     * @return array
     */
    private function handleCustomizedUrl($source_url)
    {
        $queryParams = array();
        $source_data = parse_url($source_url);
        $cqparams = array();

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
}
