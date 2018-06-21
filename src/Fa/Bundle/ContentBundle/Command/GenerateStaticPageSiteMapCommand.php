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
 * This command is used to generate static page site map.
 *
 * php app/console fa:generate:staticpage:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateStaticPageSiteMapCommand extends SiteMap
{
    /**
     * Priority in site map
     *
     * @var string
     */
    protected $priority = '0.5';

    /**
     * Frequency in site map
     *
     * @var string
     */
    protected $changeFreq = 'monthly';

    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:staticpage:sitemap')
        ->setDescription("Generate static page sitemap.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit', "768M")
        ->setHelp(
            <<<EOF
Cron: To be setup to generate static page sitemap.

Actions:
- Generate general static page sitemap.

Command:
 - php app/console fa:generate:staticpage:sitemap

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
        $fhandle = $this->generateUrlsetHeaderXml('static', $output);
        if ($fhandle) {
            $siteMapXml = '';
            $staticPages = $this->entityManager->getRepository('FaContentBundle:StaticPage')->getStaticPagesForFooter($this->getContainer());
            // static page
            foreach ($staticPages as $staticPage) {
                $siteMapXml .= $this->generateUrlTag($this->generateUrl('location_home_page', array('location' => $staticPage['slug'])));
            }
            $siteMapXml .= $this->generateUrlTag('https://blog.friday-ad.co.uk/');

            $siteMapXml .= '</urlset>';
            gzwrite($fhandle, $siteMapXml);

            gzclose($fhandle);
            $output->writeln('File "static.xml.gz" generated successfully.');
        }
        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
    }
}
