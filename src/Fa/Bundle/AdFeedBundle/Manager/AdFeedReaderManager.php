<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteDownload;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Ad feed reader manager.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdFeedReaderManager
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->dir       = $this->container->getParameter('fa.feed.data.dir');
    }

    /**
     * Download file.
     *
     * @param string  $type
     * @param integer $modifiedSince
     * @param integer $siteID
     */
    public function downloadFile($type, $modifiedSince, $siteID)
    {
        $mode = $this->container->getParameter('fa.feed.mode');
        $mainUrl = $this->container->getParameter('fa.feed.'.$mode.'.url');
        $url  = $mainUrl.'/adverts?appkey='.$this->container->getParameter('fa.feed.api.id').'&modifiedSince='.$modifiedSince.'&siteId='.$siteID.'&advertType='.$type.'&limit=1000&offset=0';

        echo "\n"."=================================================================================================="."\n";
        echo "Fetching data from:"."\n";
        echo $url."\n";
        echo "\n"."=================================================================================================="."\n";

        $first_file = $this->dir.'/'.$type.'_'.$siteID.'_'.$modifiedSince.'.json';
        $this->writeDataFromURL($url, $first_file);

        $file   = array();
        if (file_exists($first_file)) {
            $file[] =  $first_file;
            $ads = json_decode(file_get_contents($first_file), true);
            $count = $ads['TotalAdverts'];
            $step = 1000;

            for ($i = 0; $i <= $count;) {
                $i = $i + $step;
                if ($i == 0) {
                    continue;
                } else {
                     $low = $i;
                }


                $url = $mainUrl.'/adverts?appkey='.$this->container->getParameter('fa.feed.api.id').'&modifiedSince='.$modifiedSince.'&siteId='.$siteID.'&advertType='.$type.'&limit=1000&offset='.$low;

                echo "\n"."=================================================================================================="."\n";
                echo "Fetching data from:"."\n";
                echo $url."\n";
                echo "\n"."=================================================================================================="."\n";

                $nextFile = $this->dir.'/'.$type.'_'.$siteID.'_'.$modifiedSince.'_'.$low.'.json';
                $this->writeDataFromURL($url, $nextFile);
                if (file_exists($nextFile)) {
                    $file[] = $nextFile;
                }

                echo "Fetched:"."\n";
            }
            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $siteID));
            $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->deletePendingDownloadsOnNewDownload($ad_feed_site);
            $modifiedTime = new \DateTime($modifiedSince);
            $ad_feed_site_download = new AdFeedSiteDownload();
            $ad_feed_site_download->setAdFeedSite($ad_feed_site);
            $ad_feed_site_download->setModifiedSince($modifiedTime);

            $ad_feed_site_download->setStatus('P');
            $ad_feed_site_download->setFiles(serialize($file));
            $this->em->persist($ad_feed_site_download);
            $this->em->flush();

        } else {
            new Exception();
        }
    }

    /**
     * Fetch data from url.
     *
     * @param string  $url    Url.
     * @param string  $source Source file name.
     * @param boolean $binary Download as binary.
     */
    public function writeDataFromURL($url, $source, $binary = false)
    {
        $ch = curl_init($url);
        if ($binary == true) {
            $fp = fopen($source, 'wb');
        } else {
            $fp = fopen($source, 'w+');
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        if ($binary == true) {
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
     * Retrive remote file size without downloading it.
     *
     * @param string $url File url.
     *
     * @return integer
     */
    public function retrieveRemoteFileSize($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        return $size;
    }

    /**
     * Parse json file.
     *
     * @param string  $type           Ad type.
     * @param string  $file  Modified since given time.
     * @param integer $siteID         Site id.
     * @param integer $force          Force update or not.
     */
    public function parseJsonFile($type, $file, $siteID, $modified_since, $force)
    {
        if (file_exists($file)) {
            $parser = $this->createParser(ucFirst($type));
            $ad_feed_site_id =  $this->getAdFeedSiteIdByType($type, $siteID);
            $ad_feed_site_download = $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->findOneBy(array('modified_since' => $modified_since, 'ad_feed_site' => $ad_feed_site_id));
            $parser->load($file, $force, $siteID, $ad_feed_site_download);
        } else {
            new Exception("File not found");
        }
    }

    /**
     *  update Ad
     *
     * @param object  $adFeed Ad Feed object
     * @param boolean $force  update
     */
    public function updateAd($adFeed, $force = null)
    {
        $type=  $this->getAdFeedSiteTypeById($adFeed->getRefSiteId());
        $parser = $this->createParser(ucFirst($type));
        return $parser->add($adFeed, $force);
    }

    /**
     *  update Ad
     *
     * @param object  $adFeed Ad Feed object
     * @param boolean $force  update
     */
    public function updateUser($adFeed, $force = null)
    {
        $type=  $this->getAdFeedSiteTypeById($adFeed->getRefSiteId());
        $parser = $this->createParser(ucFirst($type));
        return $parser->updateUser($adFeed, $force);
    }

    /**
     * Parse json file.
     *
     * @param string  $type           Ad type.
     * @param string  $feed_file  feed file name
     * @param integer $siteID         Site id.
     */
    public function downloadImage($type, $file, $siteID, $modified_since, $force)
    {
        if (file_exists($file)) {
            $parser = $this->createParser(ucFirst($type));
            $ad_feed_site_id =  $this->getAdFeedSiteIdByType($type, $siteID);
            $ad_feed_site_download = $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->findOneBy(array('modified_since' => $modified_since, 'ad_feed_site' => $ad_feed_site_id));
            $parser->parseImages($file, $siteID, $ad_feed_site_download);
        } else {
            new Exception("File not found");
        }
    }

    /**
     * Create parser.
     *
     * @param string $type
     *
     * @return object
     */
    public function createParser($type)
    {
        if ($type == 'BoatAdvert') {
            $cname = 'boat';
        } elseif ($type == 'ClickEditVehicleAdvert') {
            $cname = 'motors';
        } elseif ($type == 'HorseAdvert') {
            $cname = 'horse';
        } elseif ($type == 'PetAdvert') {
            $cname = 'pet';
        } elseif ($type == 'PropertyAdvert') {
            $cname = 'property';
        } elseif ($type == 'MerchandiseAdvert') {
            $cname = 'merchandise';
        } elseif ($type == 'MotorhomeAdvert') {
            $cname = 'motorhome';
        } elseif ($type == 'LivestockAdvert') {
            $cname = 'livestock';
        } elseif ($type == 'JobAdvert') {
            $cname = 'job';
        } elseif ($type == 'TradeIt') {
            $cname = 'tradeit';
        } elseif ($type == 'Wightbay') {
            $cname = 'wightbay';
        } elseif ($type == 'BusinessAdvert') {
            $cname = 'business';
        } elseif ($type == 'CaravanAdvert') {
            $cname = 'caravan';
        }

        $class = 'Fa\Bundle\AdFeedBundle\Parser\\'.ucFirst($cname).'Parser';
        return new $class($this->container);
    }

    /**
     * get Ad feed site id by type
     *
     * @param string $type
     * @param integer $siteID
     * @return integer
     */
    public function getAdFeedSiteIdByType($type, $siteID = 10)
    {
        $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $siteID));

        if ($ad_feed_site) {
            return  $ad_feed_site->getId();
        }
    }

    public function getAdFeedSiteTypeById($id)
    {
        $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('id' => $id));

        if ($ad_feed_site) {
            return $ad_feed_site->getType();
        }
    }

    /**
     * Download file.
     *
     * @param string  $type
     * @param integer $modifiedSince
     * @param integer $siteID
     */
    public function downloadTradeItFile($type, $modifiedSince, $siteID)
    {
        $url = 'http://www.trade-it.co.uk/api/friday-ad/?apiToken=9dc6648bbd7eb88580a127702bf83e66&page=1';

        echo "\n"."=================================================================================================="."\n";
        echo "Fetching data from:"."\n";
        echo $url."\n";
        echo "\n"."=================================================================================================="."\n";

        $fmodifield = str_replace(' ', '_', $modifiedSince);
        $first_file = $this->dir.'/'.$type.'_'.$siteID.'_'.$fmodifield.'.json';
        $this->writeDataFromURL($url, $first_file);

        $file   = array();
        if (file_exists($first_file)) {
            $file[] =  $first_file;
            $ads = json_decode(file_get_contents($first_file), true);
            $count = $ads['totalAds'];
            $step = 1000;
            $j = 1;

            for ($i = 0; $i <= $count;) {
                if ($i == 0) {
                    $i = $i + $step;
                    continue;
                } else {
                    $i = $i + $step;
                    $low = $i;
                }

                $j++;

                $url = 'http://www.trade-it.co.uk/api/friday-ad/?apiToken=9dc6648bbd7eb88580a127702bf83e66&page='.$j;
                //$url = 'http://api.fmgfeedaggregation.com/api/v2/adverts?appkey='.$this->container->getParameter('fa.feed.api.id').'&modifiedSince='.$modifiedSince.'&siteId='.$siteID.'&advertType='.$type.'&limit=1000&offset='.$low;

                echo "\n"."=================================================================================================="."\n";
                echo "Fetching data from:"."\n";
                echo $url."\n";
                echo "\n"."=================================================================================================="."\n";

                $nextFile = $this->dir.'/'.$type.'_'.$siteID.'_'.$fmodifield.'_'.$low.'.json';
                $this->writeDataFromURL($url, $nextFile);
                if (file_exists($nextFile)) {
                    $file[] = $nextFile;
                }

                echo "Fetched:"."\n";
            }

            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $siteID));
            $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->deletePendingDownloadsOnNewDownload($ad_feed_site);
            $modifiedTime = new \DateTime($modifiedSince);
            $ad_feed_site_download = new AdFeedSiteDownload();
            $ad_feed_site_download->setAdFeedSite($ad_feed_site);
            $ad_feed_site_download->setModifiedSince($modifiedTime);

            $ad_feed_site_download->setStatus('P');
            $ad_feed_site_download->setFiles(serialize($file));
            $this->em->persist($ad_feed_site_download);
            $this->em->flush();

        } else {
            new Exception();
        }
    }

    /**
     * Download file.
     *
     * @param string  $type
     * @param integer $modifiedSince
     * @param integer $siteID
     */
    public function downloadWightbayFile($type, $modifiedSince, $siteID)
    {
        $url = 'http://www.wightbay.com/api/friday-ad/?apiToken=9dc6648bbd7eb88580a127702bf83e66&page=1';

        echo "\n"."=================================================================================================="."\n";
        echo "Fetching data from:"."\n";
        echo $url."\n";
        echo "\n"."=================================================================================================="."\n";

        $fmodifield = str_replace(' ', '_', $modifiedSince);
        $first_file = $this->dir.'/'.$type.'_'.$siteID.'_'.$fmodifield.'.json';
        $this->writeDataFromURL($url, $first_file);

        $file   = array();
        if (file_exists($first_file)) {
            $file[] =  $first_file;
            $ads = json_decode(file_get_contents($first_file), true);
            $count = $ads['totalAds'];
            $step = 1000;
            $j = 1;

            for ($i = 0; $i <= $count;) {
                if ($i == 0) {
                    $i = $i + $step;
                    continue;
                } else {
                    $i = $i + $step;
                    $low = $i;
                }

                $j++;

                $url = 'http://www.wightbay.com/api/friday-ad/?apiToken=9dc6648bbd7eb88580a127702bf83e66&page='.$j;
                //$url = 'http://api.fmgfeedaggregation.com/api/v2/adverts?appkey='.$this->container->getParameter('fa.feed.api.id').'&modifiedSince='.$modifiedSince.'&siteId='.$siteID.'&advertType='.$type.'&limit=1000&offset='.$low;

                echo "\n"."=================================================================================================="."\n";
                echo "Fetching data from:"."\n";
                echo $url."\n";
                echo "\n"."=================================================================================================="."\n";

                $nextFile = $this->dir.'/'.$type.'_'.$siteID.'_'.$fmodifield.'_'.$low.'.json';
                $this->writeDataFromURL($url, $nextFile);
                if (file_exists($nextFile)) {
                    $file[] = $nextFile;
                }

                echo "Fetched:"."\n";
            }

            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $type, 'ref_site_id' => $siteID));
            $this->em->getRepository('FaAdFeedBundle:AdFeedSiteDownload')->deletePendingDownloadsOnNewDownload($ad_feed_site);
            $modifiedTime = new \DateTime($modifiedSince);
            $ad_feed_site_download = new AdFeedSiteDownload();
            $ad_feed_site_download->setAdFeedSite($ad_feed_site);
            $ad_feed_site_download->setModifiedSince($modifiedTime);

            $ad_feed_site_download->setStatus('P');
            $ad_feed_site_download->setFiles(serialize($file));
            $this->em->persist($ad_feed_site_download);
            $this->em->flush();

        } else {
            new Exception();
        }
    }
}
