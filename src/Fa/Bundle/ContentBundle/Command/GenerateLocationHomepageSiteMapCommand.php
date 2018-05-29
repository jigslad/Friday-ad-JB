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
 * This command is used to generate location home page site map.
 *
 * php app/console fa:generate:location:homepage:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateLocationHomepageSiteMapCommand extends SiteMap
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
        ->setName('fa:generate:location:homepage:sitemap')
        ->setDescription("Generate location home page sitemap.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
        ->setHelp(
            <<<EOF
Cron: To be setup to generate location home page sitemap.

Actions:
- Generate general location home page sitemap.

Command:
 - php app/console fa:generate:location:homepage:sitemap

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
        $fhandle = $this->generateUrlsetHeaderXml('sitemap_locations', $output);
        if ($fhandle) {
            $siteMapXml = '';
            $locations = $this->getCountyTownArray($input, $output);
            // location wise landing page
            foreach ($locations as $locationId) {
                $locationSlug = $this->entityManager->getRepository('FaEntityBundle:Location')->getSlugById($locationId);
                $siteMapXml .= $this->generateUrlTag($this->generateUrl('location_home_page', array('location' => $locationSlug)));
            }

            $siteMapXml .= '</urlset>';
            gzwrite($fhandle, $siteMapXml);

            gzclose($fhandle);
            $output->writeln('File "sitemap_locations.xml.gz" generated successfully.');
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
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
