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
 * This table is used to store user site view counter.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_site_view_counter")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserSiteViewCounterRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\UserBundle\Listener\UserSiteViewCounterListener" })
 */
class UserSiteViewCounter
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
     * @ORM\Column(name="hits", type="integer", length=4, options={"default" = 0})
     */
    private $hits;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_email_sent_count", type="integer", length=4, options={"default" = 0})
     */
    private $profile_page_email_sent_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_website_url_click_count", type="integer", length=4, options={"default" = 0})
     */
    private $profile_page_website_url_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_phone_click_count", type="integer", length=4, options={"default" = 0})
     */
    private $profile_page_phone_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_social_links_click_count", type="integer", length=4, options={"default" = 0})
     */
    private $profile_page_social_links_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_page_map_click_count", type="integer", length=4, options={"default" = 0})
     */
    private $profile_page_map_click_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\UserSite
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\UserSite")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_site_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     */
    private $user_site;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

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
     * Set hits
     *
     * @param integer $hits
     * @return UserSiteViewCounter
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return integer
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set profile_page_email_sent_count
     *
     * @param integer $profile_page_email_sent_count
     * @return UserSiteViewCounter
     */
    public function setProfilePageEmailSentCount($profile_page_email_sent_count)
    {
        $this->profile_page_email_sent_count = $profile_page_email_sent_count;

        return $this;
    }

    /**
     * Get profile_page_email_sent_count
     *
     * @return integer
     */
    public function getProfilePageEmailSentCount()
    {
        return $this->profile_page_email_sent_count;
    }

    /**
     * Set profile_page_website_url_click_count
     *
     * @param integer $profile_page_website_url_click_count
     * @return UserSiteViewCounter
     */
    public function setProfilePageWebsiteUrlClickCount($profile_page_website_url_click_count)
    {
        $this->profile_page_website_url_click_count = $profile_page_website_url_click_count;

        return $this;
    }

    /**
     * Get profile_page_website_url_click_count
     *
     * @return integer
     */
    public function getProfilePageWebsiteUrlClickCount()
    {
        return $this->profile_page_website_url_click_count;
    }

    /**
     * Set profile_page_phone_click_count
     *
     * @param integer $profile_page_phone_click_count
     * @return UserSiteViewCounter
     */
    public function setProfilePagePhoneClickCount($profile_page_phone_click_count)
    {
        $this->profile_page_phone_click_count = $profile_page_phone_click_count;

        return $this;
    }

    /**
     * Get profile_page_phone_click_count
     *
     * @return integer
     */
    public function getProfilePagePhoneClickCount()
    {
        return $this->profile_page_phone_click_count;
    }

    /**
     * Set profile_page_social_links_click_count
     *
     * @param integer $profile_page_social_links_click_count
     * @return UserSiteViewCounter
     */
    public function setProfilePageSocialLinksClickCount($profile_page_social_links_click_count)
    {
        $this->profile_page_social_links_click_count = $profile_page_social_links_click_count;

        return $this;
    }

    /**
     * Get profile_page_social_links_click_count
     *
     * @return integer
     */
    public function getProfilePageSocialLinksClickCount()
    {
        return $this->profile_page_social_links_click_count;
    }

    /**
     * Set profile_page_map_click_count
     *
     * @param integer $profile_page_map_click_count
     * @return UserSiteViewCounter
     */
    public function setProfilePageMapClickCount($profile_page_map_click_count)
    {
        $this->profile_page_map_click_count = $profile_page_map_click_count;

        return $this;
    }

    /**
     * Get profile_page_map_click_count
     *
     * @return integer
     */
    public function getProfilePageMapClickCount()
    {
        return $this->profile_page_map_click_count;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return UserSiteViewCounter
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
     * Set user_site
     *
     * @param \Fa\Bundle\UserBundle\Entity\UserSite $userSite
     * @return UserSiteViewCounter
     */
    public function setUserSite(\Fa\Bundle\UserBundle\Entity\UserSite $userSite = null)
    {
        $this->user_site = $userSite;

        return $this;
    }

    /**
     * Get user_site
     *
     * @return \Fa\Bundle\UserBundle\Entity\UserSite
     */
    public function getUserSite()
    {
        return $this->user_site;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return Ad
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
}
