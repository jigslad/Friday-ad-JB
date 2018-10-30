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
 * @ORM\Table(name="user_report", indexes={@ORM\Index(name="fa_report_user_report_user_id_index", columns={"user_id"}), @ORM\Index(name="fa_report_user_report_username_index", columns={"username"}), @ORM\Index(name="fa_report_user_report_business_name_index", columns={"business_name"}), @ORM\Index(name="fa_report_user_report_role_id_index", columns={"role_id"}), @ORM\Index(name="fa_report_user_report_image_index", columns={"image"}), @ORM\Index(name="fa_report_user_report_path_index", columns={"path"}), @ORM\Index(name="fa_report_user_report_banner_path_index", columns={"banner_path"}), @ORM\Index(name="fa_report_user_report_company_address_index", columns={"company_address"}), @ORM\Index(name="fa_report_user_report_phone1_index", columns={"phone1"}), @ORM\Index(name="fa_report_user_report_phone2_index", columns={"phone2"}), @ORM\Index(name="fa_report_user_report_website_link_index", columns={"website_link"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\UserReportRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserReport
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
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", nullable=true)
     */
    private $role_id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="business_name", type="string", length=100, nullable=true)
     */
    private $business_name;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $is_active;

    /**
     * @var integer
     *
     * @ORM\Column(name="signup_date", type="integer", length=10, nullable=true)
     */
    private $signup_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="first_paa", type="integer", length=10, nullable=true)
     */
    private $first_paa;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_paa", type="integer", length=10, nullable=true)
     */
    private $last_paa;

    /**
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=20, nullable=true)
     */
    private $postcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="town_id", type="integer", nullable=true)
     */
    private $town_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_facebook_verified", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_facebook_verified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paypal_vefiried", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_paypal_vefiried;

    /**
     * @var string
     *
     * @ORM\Column(name="total_ad", type="integer", nullable=true, options={"default" = 0})
     */
    private $total_ad;

    /**
     * @var string
     *
     * @ORM\Column(name="total_active_ad", type="integer", nullable=true, options={"default" = 0})
     */
    private $total_active_ad;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=25, nullable=true)
     */
    private $phone;

    /**
     * @var integer
     *
     * @ORM\Column(name="business_category_id", type="integer", nullable=true)
     */
    private $business_category_id;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="banner_path", type="string", length=255, nullable=true)
     */
    private $banner_path;

    /**
     * @var string
     *
     * @ORM\Column(name="company_welcome_message", type="text", nullable=true)
     */
    private $company_welcome_message;

    /**
     * @var string
     *
     * @ORM\Column(name="company_address", type="string", length=255, nullable=true)
     */
    private $company_address;

    /**
     * @var string
     *
     * @ORM\Column(name="phone1", type="string", length=25, nullable=true)
     */
    private $phone1;

    /**
     * @var string
     *
     * @ORM\Column(name="phone2", type="string", length=25, nullable=true)
     */
    private $phone2;

    /**
     * @var string
     *
     * @ORM\Column(name="website_link", type="string", length=255, nullable=true)
     */
    private $website_link;

    /**
     * @var string
     *
     * @ORM\Column(name="about_you", type="text", nullable=true)
     */
    private $about_you;

    /**
     * @var string
     *
     * @ORM\Column(name="about_us", type="text", nullable=true)
     */
    private $about_us;

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

    /**
     * Set business_name.
     *
     * @param string $business_name
     * @return UserReport
     */
    public function setBusinessName($business_name)
    {
        $this->business_name = $business_name;

        return $this;
    }

    /**
     * Get business_name.
     *
     * @return string
     */
    public function getBusinessName()
    {
        return $this->business_name;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     * @return UserReport
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return UserReport
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return UserReport
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get user logo
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set user logo
     *
     * @param string $image
     * @return UserReport
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get company user logo
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set compnay user logo
     *
     * @param string $path
     * @return UserReport
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set banner_path
     *
     * @param string $banner_path
     * @return UserReport
     */
    public function setBannerPath($banner_path)
    {
        $this->banner_path = $banner_path;

        return $this;
    }

    /**
     * Get banner_path
     *
     * @return string
     */
    public function getBannerPath()
    {
        return $this->banner_path;
    }

    /**
     * Set company_welcome_message
     *
     * @param string $company_welcome_message
     * @return UserReport
     */
    public function setCompanyWelcomeMessage($company_welcome_message)
    {
        $this->company_welcome_message = $company_welcome_message;

        return $this;
    }

    /**
     * Get company_welcome_message
     *
     * @return string
     */
    public function getCompanyWelcomeMessage()
    {
        return $this->company_welcome_message;
    }

    /**
     * Set company_address
     *
     * @param string $company_address
     * @return UserReport
     */
    public function setCompanyAddress($company_address)
    {
        $this->company_address = $company_address;

        return $this;
    }

    /**
     * Get company_address
     *
     * @return string
     */
    public function getCompanyAddress()
    {
        return $this->company_address;
    }

    /**
     * Set phone1
     *
     * @param string $phone1
     * @return UserReport
     */
    public function setPhone1($phone1)
    {
        $this->phone1 = $phone1;

        return $this;
    }

    /**
     * Get phone1
     *
     * @return string
     */
    public function getPhone1()
    {
        return $this->phone1;
    }

    /**
     * Set phone2
     *
     * @param string $phone2
     * @return UserReport
     */
    public function setPhone2($phone2)
    {
        $this->phone2 = $phone2;

        return $this;
    }

    /**
     * Get phone2
     *
     * @return string
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * Set website_link
     *
     * @param string $website_link
     * @return UserReport
     */
    public function setWebsiteLink($website_link)
    {
        $this->website_link = $website_link;

        return $this;
    }

    /**
     * Get website_link
     *
     * @return string
     */
    public function getWebsiteLink()
    {
        return $this->website_link;
    }

    /**
     * Set about_us
     *
     * @param string $aboutUs
     * @return UserReport
     */
    public function setAboutUs($aboutUs)
    {
        $this->about_us = $aboutUs;

        return $this;
    }

    /**
     * Get about_us
     *
     * @return string
     */
    public function getAboutUs()
    {
        return $this->about_us;
    }

    /**
     * Set about_you
     *
     * @param string $aboutYou
     * @return UserReport
     */
    public function setAboutYou($aboutYou)
    {
        $this->about_you = $aboutYou;

        return $this;
    }

    /**
     * Get about_you
     *
     * @return string
     */
    public function getAboutYou()
    {
        return $this->about_you;
    }
}
