<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to ad report.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_feed_click_report_daily", indexes={@ORM\Index(name="fa_report_ad_feed_click_report_daily_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fa_report_ad_feed_click_report_daily_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_ad_feed_click_report_daily_index", columns={"ad_feed_site_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\AdFeedClickReportDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdFeedClickReportDaily
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
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer")
     */
    private $ad_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_feed_site_id", type="integer")
     */
    private $ad_feed_site_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="view", type="integer", nullable=true, options={"default" = 0})
     */
    private $view;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10, nullable=true)
     */
    private $created_at;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get ad_id
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
    }

    /**
     * Set ad_id
     *
     * @param integer $adId
     * @return AdReportEnquiryDaily
     */
    public function setAdId($adId)
    {
        $this->ad_id = $adId;

        return $this;
    }

    /**
     * Get ad_feed_site_id
     *
     * @return integer
     */
    public function getAdFeedSiteId()
    {
        return $this->ad_feed_site_id;
    }
    
    /**
     * Set ad_feed_site_id
     *
     * @param integer $adFeedSiteId
     * @return AdReportEnquiryDaily
     */
    public function setAdFeedSiteId($adFeedSiteId)
    {
        $this->ad_feed_site_id = $adFeedSiteId;
    
        return $this;
    }

    /**
     * Get view
     *
     * @return integer
     */
    public function getView()
    {
        return $this->view;
    }
    
    /**
     * Set view
     *
     * @param integer $view
     * @return AdReportEnquiryDaily
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return AdReportEnquiryDaily
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }
}
