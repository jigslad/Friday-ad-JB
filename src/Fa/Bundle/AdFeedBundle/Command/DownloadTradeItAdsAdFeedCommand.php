<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is used to download trade it feed.
 *
 * php app/console fa:download:feed download  --type="gun" --site_id="8"
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DownloadTradeItAdsAdFeedCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:tradeit-ad:download')
        ->setDescription('Download feed file for given type and modified time')
        ->addOption('modified_since', null, InputOption::VALUE_OPTIONAL, 'modified since', null);
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object
     * @param OutputInterface $output OutputInterface object
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Command Started At: ".date('Y-m-d H:i:s', time())."\n";
        $feedReader = $this->getContainer()->get('fa_ad.manager.ad_feed_reader');

        $type = 'TradeIt';
        $site_id = 1;
        $modified_since = $this->getLastModifiedTime($type, $site_id);
        if ($modified_since && $modified_since->getLastRunTime() != '') {
            $modified_since = $modified_since->getLastRunTime()->format('Y-m-d H:i:s');
        } else {
            date_default_timezone_set('UTC');
            $modified_since = gmdate('Y-m-d H:i:s', strtotime('-2 hour'));
        }

        if ($input->getOption('modified_since')) {
            $modified_since = $input->getOption('modified_since');
        }
        date_default_timezone_set(ini_get('date.timezone'));

        $this->removeOldJsonFile($this->getContainer()->getParameter('fa.feed.data.dir').'/'.$type.'_'.$site_id.'_*.json');

        $feedReader->downloadTradeItFile($type, $modified_since, $site_id);

        $downloadedFile = $this->getContainer()->getParameter('fa.feed.data.dir').'/'.$type.'_'.$site_id.'_'.$modified_since.'.json';

        $files = glob($this->getContainer()->getParameter('fa.feed.data.dir').'/'.$type.'_'.$site_id.'_*.json');
        foreach ($files as $file) {
            if (is_file($file)) {
                echo $file.": Downloaded"."\n";
            }
        }

        echo "\n"."Command Ended At: ".date('Y-m-d H:i:s', time())."\n"."\n";
    }


    /**
     * Remove extra images.
     *
     * @param string $dir        Directory path.
     * @param string $productId  Product id.
     * @param array  $imageArray Image array.
     */
    public function removeOldJsonFile($pathPattern)
    {
        $files = glob($pathPattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                echo $file.": remove old json file \n";
                unlink($file);
            }
        }
    }

    /**
     * Get last modified time.
     *
     * @param string  $type
     * @param integer $site_id
     *
     * @return Object.
     */
    public function getLastModifiedTime($type, $site_id)
    {
        $ad_feed_site = $this->getContainer()->get('doctrine')->getManager()->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $site_id));

        if ($ad_feed_site) {
            return $this->getContainer()->get('doctrine')->getManager()->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->getLatestModifiedTimeForDownload($ad_feed_site->getId());
        }
    }
}
