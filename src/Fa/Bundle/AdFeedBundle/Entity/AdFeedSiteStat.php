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
 * @ORM\Table(name="ad_feed_site_stat")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\AdFeedSiteStatRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdFeedSiteStat
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
     * @var \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdFeedBundle\Entity\AdFeedSite")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_feed_site_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad_feed_site;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_new", type="integer")
     */
    private $total_new;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_update", type="integer")
     */
    private $total_update;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_not_update", type="integer")
     */
    private $total_not_update;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_closed", type="integer")
     */
    private $total_closed;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_idle", type="integer")
     */
    private $total_idle;

    /**
     * @var \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteDownload
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteDownload")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_feed_site_download_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad_feed_site_download;

    /**
     * Get id.
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param integer $id
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteUser
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
     * Set ad.
     *
     * @param \Fa\Bundle\AdFeedBundle\Entity\AdFeedSite $ad
     * @return AdFeed
     */
    public function setAdFeedSite(\Fa\Bundle\AdFeedBundle\Entity\AdFeedSite $ad_feed_site = null)
    {
        $this->ad_feed_site = $ad_feed_site;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return AdFeed
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get ad feed site download.
     */
    public function getAdFeedSiteDownload()
    {
        return $this->ad_feed_site_download;
    }

    /**
     * Set ad feed site download.
     *
     * @param \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteDownload $ad_feed_site_download
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteStat
     */
    public function setAdFeedSiteDownload(\Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteDownload $ad_feed_site_download = null)
    {
        $this->ad_feed_site_download = $ad_feed_site_download;
        return $this;
    }

    /**
     * Get total new.
     */
    public function getTotalNew()
    {
        return $this->total_new;
    }

    /**
     * Set total new.
     *
     * @param $total_new Total new.
     */
    public function setTotalNew($total_new)
    {
        $this->total_new = $total_new;
        return $this;
    }

    /**
     * Get total update.
     */
    public function getTotalUpdate()
    {
        return $this->total_update;
    }

    /**
     * Set total update.
     *
     * @param integer $total_update
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteStat
     */

    public function setTotalUpdate($total_update)
    {
        $this->total_update = $total_update;
        return $this;
    }

    /**
     * Get total closed.
     */
    public function getTotalClosed()
    {
        return $this->total_closed;
    }

    /**
     * Set total closed.
     *
     * @param integer $total_closed
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeedSiteStat
     */
    public function setTotalClosed($total_closed)
    {
        $this->total_closed = $total_closed;
        return $this;
    }

    /**
     * Get total idle.
     */
    public function getTotalIdle()
    {
        return $this->total_idle;
    }

    /**
     * Set total idle.
     *
     * @param $total_idle
     */
    public function setTotalIdle($total_idle)
    {
        $this->total_idle = $total_idle;
        return $this;
    }

    /**
     * Set total idle.
     */
    public function getTotalNotUpdate()
    {
        return $this->total_not_update;
    }

    /**
     * Set total not update.
     *
     * @param $total_not_update Total not update.
     */
    public function setTotalNotUpdate($total_not_update)
    {
        $this->total_not_update = $total_not_update;
        return $this;
    }
}
