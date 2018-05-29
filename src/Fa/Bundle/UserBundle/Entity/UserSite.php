<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store site information of user.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_site")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserSiteRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\UserBundle\Listener\UserSiteListener" })
 */
class UserSite
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
     * @var string
     *
     * @ORM\Column(name="website_link", type="string", length=20, nullable=true)
     */
    /**
     * @var string
     *
     * @ORM\Column(name="website_link", type="string", length=255, nullable=true)
     */
    private $website_link;

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
     * @ORM\Column(name="about_us", type="text", nullable=true)
     */
    private $about_us;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_ref", type="string", length=255, nullable=true)
     */
    private $ad_ref;

    /**
     * @var string
     *
     * @ORM\Column(name="company_address", type="string", length=255, nullable=true)
     */
    private $company_address;

    /**
     * @var string
     *
     * @ORM\Column(name="company_welcome_message", type="text", nullable=true)
     */
    private $company_welcome_message;

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
     * @ORM\Column(name="list_type", type="string", length=10, nullable=true)
     */
    private $list_type;

    /**
     * @var string
     *
     * @ORM\Column(name="background_color", type="string", length=10, nullable=true)
     */
    private $background_color;

    /**
     * @var string
     *
     * @ORM\Column(name="logo_back", type="string", length=10, nullable=true)
     */
    private $logo_back;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_exposure_category_id", type="integer", length=10, nullable=true)
     */
    private $profile_exposure_category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\OneToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_url", type="string", length=100, nullable=true)
     */
    private $facebook_url;

    /**
     * @var string
     *
     * @ORM\Column(name="google_url", type="string", length=100, nullable=true)
     */
    private $google_url;

    /**
     * @var string
     *
     * @ORM\Column(name="twitter_url", type="string", length=100, nullable=true)
     */
    private $twitter_url;

    /**
     * @var string
     *
     * @ORM\Column(name="pinterest_url", type="string", length=100, nullable=true)
     */
    private $pinterest_url;

    /**
     * @var string
     *
     * @ORM\Column(name="instagram_url", type="string", length=100, nullable=true)
     */
    private $instagram_url;

    /**
     * @var string
     *
     * @ORM\Column(name="youtube_video_url", type="string", length=100, nullable=true)
     */
    private $youtube_video_url;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=100, nullable=true)
     */
    private $slug;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_map", type="boolean", nullable=true, options={"default" = 0})
     */
    private $show_map = 0;

    /**
     * Set created at value.
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set website_link
     *
     * @param string $website_link
     * @return UserSite
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
     * Set path
     *
     * @param string $path
     * @return UserSite
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set ad_ref
     *
     * @param string $ad_ref
     * @return UserSite
     */
    public function setAdRef($ad_ref)
    {
        $this->ad_ref = $ad_ref;

        return $this;
    }

    /**
     * Get ad_ref
     *
     * @return string
     */
    public function getAdRef()
    {
        return $this->ad_ref;
    }

    /**
     * Set about_us
     *
     * @param string $aboutUs
     * @return UserSite
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
     * Set status
     *
     * @param boolean $status
     * @return UserSite
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set company_address
     *
     * @param string $company_address
     * @return UserSite
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
     * Set company_welcome_message
     *
     * @param string $company_welcome_message
     * @return UserSite
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
     * Set list_type
     *
     * @param string $listType
     * @return UserSite
     */
    public function setListType($listType)
    {
        $this->list_type = $listType;

        return $this;
    }

    /**
     * Get list_type
     *
     * @return string
     */
    public function getListType()
    {
        return $this->list_type;
    }

    /**
     * Set background_color
     *
     * @param string $backgroundColor
     * @return UserSite
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->background_color = $backgroundColor;

        return $this;
    }

    /**
     * Get background_color
     *
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->background_color;
    }

    /**
     * Set logo_back
     *
     * @param string $logoBack
     * @return UserSite
     */
    public function setLogoBack($logoBack)
    {
        $this->logo_back = $logoBack;

        return $this;
    }

    /**
     * Get logo_back
     *
     * @return string
     */
    public function getLogoBack()
    {
        return $this->logo_back;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return UserSite
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
     * @return UserSite
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
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserSite
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set phone1
     *
     * @param string $phone1
     * @return User
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
     * @return User
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
     * Set facebook_url.
     *
     * @param string $facebook_url
     * @return UserSite
     */
    public function setFacebookUrl($facebook_url)
    {
        $this->facebook_url = $facebook_url;

        return $this;
    }

    /**
     * Get facebook_url.
     *
     * @return string
     */
    public function getFacebookUrl()
    {
        return $this->facebook_url;
    }

    /**
     * Set google_url.
     *
     * @param string $google_url
     * @return UserSite
     */
    public function setGoogleUrl($google_url)
    {
        $this->google_url = $google_url;

        return $this;
    }

    /**
     * Get google_url.
     *
     * @return string
     */
    public function getGoogleUrl()
    {
        return $this->google_url;
    }

    /**
     * Set twitter_url.
     *
     * @param string $twitter_url
     * @return UserSite
     */
    public function setTwitterUrl($twitter_url)
    {
        $this->twitter_url = $twitter_url;

        return $this;
    }

    /**
     * Get twitter_url.
     *
     * @return string
     */
    public function getTwitterUrl()
    {
        return $this->twitter_url;
    }

    /**
     * Set pinterest_url.
     *
     * @param string $pinterest_url
     * @return UserSite
     */
    public function setPinterestUrl($pinterest_url)
    {
        $this->pinterest_url = $pinterest_url;

        return $this;
    }

    /**
     * Get pinterest_url.
     *
     * @return string
     */
    public function getPinterestUrl()
    {
        return $this->pinterest_url;
    }

    /**
     * Set youtube_video_url.
     *
     * @param string $youtube_video_url
     * @return UserSite
     */
    public function setYoutubeVideoUrl($youtube_video_url)
    {
        $this->youtube_video_url = $youtube_video_url;

        return $this;
    }

    /**
     * Get youtube_video_url.
     *
     * @return string
     */
    public function getYoutubeVideoUrl()
    {
        return $this->youtube_video_url;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     * @return UserSite
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set banner_path
     *
     * @param string $banner_path
     * @return UserSite
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
     * Set profile_exposure_category_id.
     *
     * @param integer $profile_exposure_category_id
     * @return UserSite
     */
    public function setProfileExposureCategoryId($profile_exposure_category_id)
    {
        $this->profile_exposure_category_id = $profile_exposure_category_id;

        return $this;
    }

    /**
     * Get profile_exposure_category_id.
     *
     * @return integer
     */
    public function getProfileExposureCategoryId()
    {
        return $this->profile_exposure_category_id;
    }

    /**
     * Set show_map.
     *
     * @param boolean $show_map
     * @return UserSite
     */
    public function setShowMap($show_map)
    {
        $this->show_map = $show_map;

        return $this;
    }

    /**
     * Get show_map.
     *
     * @return boolean
     */
    public function getShowMap()
    {
        return $this->show_map;
    }

    /**
     * Set instagram_url.
     *
     * @param string $instagram_url
     * @return UserSite
     */
    public function setInstagramUrl($instagram_url)
    {
        $this->instagram_url = $instagram_url;

        return $this;
    }

    /**
     * Get instagram_url.
     *
     * @return string
     */
    public function getInstagramUrl()
    {
        return $this->instagram_url;
    }
}
