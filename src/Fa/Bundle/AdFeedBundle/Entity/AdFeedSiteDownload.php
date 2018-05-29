<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_feed_site_download")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\AdFeedSiteDownloadRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdFeedSiteDownload
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_since", type="datetime", nullable=true)
     */
    private $modified_since;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_run_time", type="datetime", nullable=true)
     */
    private $last_run_time;

    /**
     * @var \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdFeedBundle\Entity\AdFeedSite")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_feed_site_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad_feed_site;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=1)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="files", type="text")
     */
    private $files;

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return integer
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get ad feed site.
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite
     */
    public function getAdFeedSite()
    {
        return $this->ad_feed_site;
    }

    /**
     * Set ad feed site.
     *
     * @param \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite $ad
     *
     * @return AdFeed
     */
    public function setAdFeedSite(\Fa\Bundle\AdFeedBundle\Entity\AdFeedSite $ad_feed_site = null)
    {
        $this->ad_feed_site = $ad_feed_site;

        return $this;
    }

    /**
     * Get modified since.
     *
     * @return \DateTime
     */
    public function getModifiedSince()
    {
        return $this->modified_since;
    }

    /**
     * Set modified since.
     *
     * @param \DateTime $type
     *
     */
    public function setModifiedSince(\DateTime $modified_since)
    {
        $this->modified_since = $modified_since;
        return $this;
    }

    /**
     * Get last run time
     *
     * @return string
     */
    public function getLastRunTime()
    {
        return $this->last_run_time;
    }

    /**
     * Set last run time
     *
     * @param \DateTime $type
     *
     */
    public function setLastRunTime(\DateTime $last_run_time)
    {
        $this->last_run_time = $last_run_time;
        return $this;
    }

    /**
     * Get status.
     *
     * @return the string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get files.
     *
     * @return the string
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set files.
     *
     * @param string $files
     */
    public function setFiles($files)
    {
        $this->files = $files;
        return $this;
    }
}
