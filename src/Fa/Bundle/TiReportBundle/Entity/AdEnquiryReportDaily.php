<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to ad report.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_enquiry_report_daily", indexes={@ORM\Index(name="fa_report_ad_enquiry_report_daily_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fa_report_ad_enquiry_report_daily_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_ad_enquiry_report_daily_package_price_index", columns={"package_price"}), @ORM\Index(name="fa_report_ad_enquiry_report_daily_role_id_index", columns={"role_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\TiReportBundle\Repository\AdEnquiryReportDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdEnquiryReportDaily
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
     * @ORM\Column(name="view", type="integer", nullable=true, options={"default" = 0})
     */
    private $view;

    /**
     * @var integer
     *
     * @ORM\Column(name="contact_seller_click", type="integer", nullable=true, options={"default" = 0})
     */
    private $contact_seller_click;

    /**
     * @var integer
     *
     * @ORM\Column(name="call_click", type="integer", nullable=true, options={"default" = 0})
     */
    private $call_click;

    /**
     * @var integer
     *
     * @ORM\Column(name="email_send_link", type="integer", nullable=true, options={"default" = 0})
     */
    private $email_send_link;

    /**
     * @var integer
     *
     * @ORM\Column(name="social_share", type="integer", nullable=true, options={"default" = 0})
     */
    private $social_share;

    /**
     * @var integer
     *
     * @ORM\Column(name="web_link_click", type="integer", nullable=true, options={"default" = 0})
     */
    private $web_link_click;

    /**
     * @var string
     *
     * @ORM\Column(name="package_name", type="string", length=255, nullable=true)
     */
    private $package_name;

    /**
     * @var float
     *
     * @ORM\Column(name="package_price", type="float", precision=15, scale=2, nullable=true)
     */
    private $package_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_site_view_counter", type="integer", length=4, nullable=true)
     */
    private $user_site_view_counter;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", nullable=true)
     */
    private $role_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10, nullable=true)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

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
     * Get ad_id
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
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
     * Get view
     *
     * @return integer
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set contact_seller_click
     *
     * @param integer $contactSellerClick
     * @return AdReportEnquiryDaily
     */
    public function setContactSellerClick($contactSellerClick)
    {
        $this->contact_seller_click = $contactSellerClick;

        return $this;
    }

    /**
     * Get contact_seller_click
     *
     * @return integer
     */
    public function getContactSellerClick()
    {
        return $this->contact_seller_click;
    }

    /**
     * Set call_click
     *
     * @param integer $callClick
     * @return AdReportEnquiryDaily
     */
    public function setCallClick($callClick)
    {
        $this->call_click = $callClick;

        return $this;
    }

    /**
     * Get call_click
     *
     * @return integer
     */
    public function getCallClick()
    {
        return $this->call_click;
    }

    /**
     * Set email_send_link
     *
     * @param integer $emailSendLink
     * @return AdReportEnquiryDaily
     */
    public function setEmailSendLink($emailSendLink)
    {
        $this->email_send_link = $emailSendLink;

        return $this;
    }

    /**
     * Get email_send_link
     *
     * @return integer
     */
    public function getEmailSendLink()
    {
        return $this->email_send_link;
    }

    /**
     * Set social_share
     *
     * @param integer $socialShare
     * @return AdReportEnquiryDaily
     */
    public function setSocialShare($socialShare)
    {
        $this->social_share = $socialShare;

        return $this;
    }

    /**
     * Get social_share
     *
     * @return integer
     */
    public function getSocialShare()
    {
        return $this->social_share;
    }

    /**
     * Set web_link_click
     *
     * @param integer $webLinkClick
     * @return AdReportEnquiryDaily
     */
    public function setWebLinkClick($webLinkClick)
    {
        $this->web_link_click = $webLinkClick;

        return $this;
    }

    /**
     * Get web_link_click
     *
     * @return integer
     */
    public function getWebLinkClick()
    {
        return $this->web_link_click;
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
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return AdReportEnquiryDaily
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set package_name.
     *
     * @param string $package_name
     * @return AdEnquiryReportDaily
     */
    public function setPackageName($package_name)
    {
        $this->package_name = $package_name;

        return $this;
    }

    /**
     * Get package_name.
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->package_name;
    }

    /**
     * Set package_price.
     *
     * @param string $package_price
     * @return AdEnquiryReportDaily
     */
    public function setPackagePrice($package_price)
    {
        $this->package_price = $package_price;

        return $this;
    }

    /**
     * Get package_price.
     *
     * @return string
     */
    public function getPackagePrice()
    {
        return $this->package_price;
    }

    /**
     * Set user_site_view_counter.
     *
     * @param string $user_site_view_counter
     * @return AdEnquiryReportDaily
     */
    public function setUserSiteViewCounter($user_site_view_counter)
    {
        $this->user_site_view_counter = $user_site_view_counter;

        return $this;
    }

    /**
     * Get user_site_view_counter.
     *
     * @return string
     */
    public function getUserSiteViewCounter()
    {
        return $this->user_site_view_counter;
    }
}
