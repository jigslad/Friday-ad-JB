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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This file is used to write common functions.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 */
class SiteMap extends ContainerAwareCommand
{
    /**
     * Site map generate url
     *
     * @var string
     */
    protected $siteMapXmlUrl;

    /**
     * Frequency in site map
     *
     * @var string
     */
    protected $changeFreq = 'daily';

    /**
     * Priority in site map
     *
     * @var string
     */
    protected $priority = '1.0';

    /**
     * Last modify date
     *
     * @var date
     */
    protected $lastModify;

    /**
     * Default entity manager
     *
     * @var object
     */
    protected $entityManager;

    /**
     * Initialize parameters
     */
    protected function initializeSiteMapParameters()
    {
        $this->entityManager  = $this->getContainer()->get('doctrine')->getManager();
        $this->siteMapXmlPath = $this->getContainer()->get('kernel')->getRootDir().'/../web/uploads/sitemap';
        $this->lastModify     = date('c');
    }

    /**
     * Generate urlset header xml
     *
     * @param string  $fileName    File name.
     * @param object  $output      Output object.
     * @param boolean $removeFiles Remove old files.
     *
     * @return resource|boolean
     */
    protected function generateUrlsetHeaderXml($fileName, $output, $removeFiles = true)
    {
        $siteMapXml  = '';
        $siteMapXml .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $siteMapXml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

        $file = $this->siteMapXmlPath.'/'.$fileName.'.xml.gz';
        if ($removeFiles) {
            if (file_exists($file)) {
                unlink($file);
            }

            $deleteFiles = glob($this->siteMapXmlPath.'/'.$fileName.'*');
            foreach ($deleteFiles as $deleteFile) {
                if (file_exists($deleteFile)) {
                    unlink($deleteFile); // delete file
                }
            }
        }
        $fhandle = gzopen($file, 'ab');
        if ($fhandle) {
            gzwrite($fhandle, $siteMapXml);
            return $fhandle;
        } else {
            $output->writeln('Can not update file  '.$file."\n");
            return false;
        }
    }

    /**
     * This function is used to generate sitemap url tag
     *
     * @param string $url url string
     *
     * @return string
     */
    protected function generateUrlTag($url)
    {
        $siteMapXml  = '';
        if ($url) {
            $siteMapXml .= "\t<url>\n";
            $siteMapXml .= "\t\t<loc>".$url."</loc>\n";

            if ($this->lastModify && $this->lastModify != '0000-00-00') {
                $siteMapXml .= "\t\t<lastmod>".date('c', strtotime($this->lastModify))."</lastmod>\n";
            }

            $siteMapXml .= "\t\t<changefreq>".$this->changeFreq."</changefreq>\n";
            $siteMapXml .= "\t\t<priority>".$this->priority."</priority>\n";
            $siteMapXml .= "\t</url>\n";
        }

        return $siteMapXml;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string      $route         The name of the route
     * @param mixed       $parameters    An array of parameters
     * @param bool|string $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_URL)
    {
        try {
            return $this->getContainer()->get('router')->generate($route, $parameters, $referenceType);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Split xmls.
     *
     * @param string $fileName File name.
     * @param object $output   Output object.
     */
    protected function splitXml($fileName, $output)
    {
        $command = "gunzip ".$this->siteMapXmlPath.'/'.$fileName.'.xml.gz';
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Problem in gunzip.');
        }

        $command = "xml_split -g50000 ".$this->siteMapXmlPath.'/'.$fileName.'.xml';
        passthru($command, $returnVar);

        if ($returnVar !== 0) {
            new \Exception('Problem in split.');
        }

        $files = glob($this->siteMapXmlPath.'/'.$fileName.'-*');

        $i = 0;
        $output->writeln('Processing multiple files...');
        if (count($files) == 2) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $command = "gzip ".$this->siteMapXmlPath.'/'.$fileName.'.xml';
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                new \Exception('Problem in gzip.');
            }
        } else {
            foreach ($files as $file) {
                if ($i == 0) {
                    $i++;
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    continue;
                }

                rename($file, $this->siteMapXmlPath.'/'.$fileName.'_'.$i.'.xml');
                $command = "sed -i 's/<xml_split:root xmlns:xml_split=\"http:\/\/xmltwig.com\/xml_split\">/<urlset xmlns=\"http:\/\/www.sitemaps.org\/schemas\/sitemap\/0.9\" xmlns:xsi=\"http:\/\/www.w3.org\/2001\/XMLSchema-instance\" xsi:schemaLocation=\"http:\/\/www.sitemaps.org\/schemas\/sitemap\/0.9 http:\/\/www.sitemaps.org\/schemas\/sitemap\/0.9\/sitemap.xsd\">/' ".$this->siteMapXmlPath.'/'.$fileName.'_'.$i.'.xml';
                passthru($command, $returnVar);

                if ($returnVar !== 0) {
                    new \Exception('Problem in replacing split header tag.');
                }

                $command = "sed -i 's/<\/xml_split:root>/<\\/urlset>/' ".$this->siteMapXmlPath.'/'.$fileName.'_'.$i.'.xml';
                passthru($command, $returnVar);

                if ($returnVar !== 0) {
                    new \Exception('Problem in replacing split footer tag.');
                }

                $command = "gzip ".$this->siteMapXmlPath.'/'.$fileName.'_'.$i.'.xml';
                passthru($command, $returnVar);

                if ($returnVar !== 0) {
                    new \Exception('Problem in gzip.');
                }

                if (file_exists($this->siteMapXmlPath.'/'.$fileName.'.xml')) {
                    unlink($this->siteMapXmlPath.'/'.$fileName.'.xml');
                }
                $i++;
            }
        }
    }
}
