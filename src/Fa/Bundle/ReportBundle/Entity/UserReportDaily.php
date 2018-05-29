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
 * This table is used to store information related to dotmailer.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_report_daily", indexes={@ORM\Index(name="fa_report_user_report_daily_user_id_index", columns={"user_id"}), @ORM\Index(name="fa_report_user_report_daily_created_at_index", columns={"created_at"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\UserReportDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserReportDaily
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
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", nullable=true)
     */
    private $role_id;


    /**
     * @var string
     *
     * @ORM\Column(name="number_of_active_ads", type="smallint", options={"default" = 0})
     */
    private $number_of_active_ads = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="renewed_ads", type="smallint", options={"default" = 0})
     */
    private $renewed_ads = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="expired_ads", type="smallint", options={"default" = 0})
     */
    private $expired_ads = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="cancelled_ads", type="smallint", options={"default" = 0})
     */
    private $cancelled_ads = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_ad_placed", type="smallint", options={"default" = 0})
     */
    private $number_of_ad_placed = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_ad_sold", type="smallint", options={"default" = 0})
     */
    private $number_of_ad_sold = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="number_of_ads_to_renew", type="smallint", options={"default" = 0})
     */
    private $number_of_ads_to_renew = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="saved_searches", type="smallint", options={"default" = 0})
     */
    private $saved_searches = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="total_spent", type="float", precision=15, scale=2)
     */
    private $total_spent = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_view_count", type="integer", length=4)
     */
    private $profile_page_view_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_email_sent_count", type="integer", length=4)
     */
    private $profile_page_email_sent_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_website_url_click_count", type="integer", length=4)
     */
    private $profile_page_website_url_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_phone_click_count", type="integer", length=4)
     */
    private $profile_page_phone_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_social_links_click_count", type="integer", length=4)
     */
    private $profile_page_social_links_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_map_click_count", type="integer", length=4)
     */
    private $profile_page_map_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10)
     */
    private $updated_at;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set updated at value.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
     *
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return Message
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set user_id.
     *
     * @param string $user_id
     * @return UserReport
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }
}
