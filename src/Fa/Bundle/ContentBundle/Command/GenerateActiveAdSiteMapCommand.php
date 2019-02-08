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
 * This command is used to generate active ad site map.
 *
 * php app/console fa:generate:active:ad:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateActiveAdSiteMapCommand extends SiteMap
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * Priority in site map
     *
     * @var string
     */
    protected $priority = '0.6';

    /**
     * Frequency in site map
     *
     * @var string
     */
    protected $changeFreq = 'weekly';

    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:active:ad:sitemap')
        ->setDescription("Generate active ad sitemap.")
        ->addOption('root_category_id', null, InputOption::VALUE_REQUIRED, 'Root level category')
        ->addOption('file_name', null, InputOption::VALUE_REQUIRED, 'File name of site map')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to generate active ad sitemap.

Actions:
- Generate active ad  sitemap.

Command:
 - php app/console fa:generate:active:ad:sitemap --root_category_id=2 --file_name=sitemap_forsale_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=444 --file_name=sitemap_motors_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=725 --file_name=sitemap_animals_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=500 --file_name=sitemap_jobs_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=585 --file_name=sitemap_services_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=678 --file_name=sitemap_property_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=783 --file_name=sitemap_community_ads
 - php app/console fa:generate:active:ad:sitemap --root_category_id=3411 --file_name=sitemap_adult_ads

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

        if (isset($offset)) {
            $this->generateActiveAdLinksWithOffset($input, $output);
        } else {
            $fhandle = $this->generateUrlsetHeaderXml($fileName, $output);
            gzclose($fhandle);
            $this->generateActiveAdLinks($input, $output);
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
     * Generate active ad links.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function generateActiveAdLinks($input, $output)
    {
        $count  = $this->getActiveAdsCount($input, $output);

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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:generate:active:ad:sitemap '.$commandOptions.' --verbose';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Generate uk level categories links.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function getActiveAdsCount($input, $output)
    {
        $rootCategoryId = $input->getOption('root_category_id');
        $data                 = array();
        $data['select_fields'] = array('ad' => array('id'));
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['category_id'] = $rootCategoryId;
        $data['static_filters'] = ' AND -a_track_back_url_s:[* TO *]';

        // initialize solr search manager service and fetch data based of above prepared search options
        $this->getContainer()->get('fa.solrsearch.manager')->init('ad', '', $data);
        $solrResponse = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponse();

        return $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);
    }

    /**
     * Generate active ad links with offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function generateActiveAdLinksWithOffset($input, $output)
    {
        $fileName = $input->getOption('file_name');
        $rootCategoryId = $input->getOption('root_category_id');
        $offset = $input->getOption('offset');
        $routingManager = $this->getContainer()->get('fa_ad.manager.ad_routing');
        $file = $this->siteMapXmlPath.'/'.$fileName.'.xml.gz';
        $fhandle = gzopen($file, 'ab');
        $page = 1;


        if ($offset > 0) {
            $page = ($offset/$this->limit) + 1;
        }

        if ($fhandle) {
            $data                 = array();
            $data['select_fields'] = array('ad' => array('id', 'title', 'CATEGORY_ID', 'TOWN_ID', 'DOMICILE_ID'));
            $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
            $data['query_filters']['item']['category_id'] = $rootCategoryId;
            $data['static_filters'] = ' AND -a_track_back_url_s:[* TO *]';

            // initialize solr search manager service and fetch data based of above prepared search options
            $this->getContainer()->get('fa.solrsearch.manager')->init('ad', '', $data, $page, $this->limit);
            $solrResponse = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponse();
            $result      = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
            $resultCount = $this->getContainer()->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);

            // initialize pagination manager service and prepare listing with pagination based of solr result
            $this->getContainer()->get('fa.pagination.manager')->init($result, $page, $this->limit, $resultCount);
            $pagination = $this->getContainer()->get('fa.pagination.manager')->getSolrPagination();

            $siteMapXml = '';
            if ($pagination->getNbResults()) {
                foreach ($pagination->getCurrentPageResults() as $ad) {
                    try {
                        $adUrl = $routingManager->getDetailUrl($ad);
                        $siteMapXml .= $this->generateUrlTag($adUrl);
                    } catch (\Exception $e) {
                    }
                }
            }

            gzwrite($fhandle, $siteMapXml);
            gzclose($fhandle);
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }
}
