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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * This command is used to generate location home page site map.
 *
 * php app/console fa:generate:sitemap
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class GenerateSiteMapCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('fa:generate:sitemap')
        ->setDescription("Generate sitemap.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', "768M")
        ->setHelp(
            <<<EOF
Cron: To be setup to generate sitemap.

Actions:
- Generate general sitemap.

Command:
 - php app/console fa:generate:sitemap

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
        $webRootPath = $this->getContainer()->get('kernel')->getRootDir().'/../web';
        $siteMapXmlPath = $webRootPath.'/uploads/sitemap';

        $siteMapXml  = '';
        $siteMapXml .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $siteMapXml .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd\">\n";

        $dh = opendir($siteMapXmlPath);
        if ($dh) {
            while ($filename = readdir($dh)) {
                if ($filename != '.' && $filename != '..' && $filename != '.keepme') {
                    $siteMapXml .= "\t<sitemap>\n";
                    $siteMapXml .= "\t\t<loc>".$this->getContainer()->getParameter('base_url').'/uploads/sitemap/'.$filename."</loc>\n";
                    $siteMapXml .= "\t\t<lastmod>".date('c')."</lastmod>\n";
                    $siteMapXml .= "\t</sitemap>\n";
                }
            }
        }

        $siteMapXml .= "</sitemapindex>\n";

        $fname = fopen($webRootPath.'/sitemap.xml', 'w+');

        if ($fname) {
            fwrite($fname, $siteMapXml);
            fclose($fname);

            $output->writeln("sitemap.xml created successfully.");
        } else {
            $output->writeln("Can not create sitemap.xml.");
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $start_time) / 60), true);
    }
}
