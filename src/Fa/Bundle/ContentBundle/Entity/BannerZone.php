<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * This table is used to store information about static page.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="banner_zone")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\BannerZoneRepository")
 * @ORM\HasLifecycleCallbacks
 */
class BannerZone
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
     * @ORM\Column(name="name", type="string", length=150)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=150)
     */
    private $slug;

    /**
     * @var smallint
     *
     * @ORM\Column(name="max_width", type="smallint", nullable=true)
     */
    private $max_width;

    /**
     * @var smallint
     *
     * @ORM\Column(name="max_height", type="smallint", nullable=true)
     */
    private $max_height;

    /**
     * @var string
     *
     * @ORM\Column(name="is_desktop", type="boolean", options={"default" = 0})
     */
    private $is_desktop = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="is_tablet", type="boolean", options={"default" = 0})
     */
    private $is_tablet = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="is_mobile", type="boolean", options={"default" = 0})
     */
    private $is_mobile = 0;

    /** @var integer
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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Fa\Bundle\ContentBundle\Entity\BannerPage")
     * @ORM\JoinTable(name="banner_zone_banner_page",
     *   joinColumns={
     *     @ORM\JoinColumn(name="banner_zone_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="banner_page_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     */
    private $banner_pages;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

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
     * @return BannerZone
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return BannerZone
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return BannerZone
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set max_width
     *
     * @param integer $max_width
     * @return BannerZone
     */
    public function setMaxWidth($max_width)
    {
        $this->max_width = $max_width;

        return $this;
    }

    /**
     * Get max_width
     *
     * @return integer
     */
    public function getMaxWidth()
    {
        return $this->max_width;
    }

    /**
     * Set max_height
     *
     * @param integer $max_height
     * @return BannerZone
     */
    public function setMaxHeight($max_height)
    {
        $this->max_height = $max_height;

        return $this;
    }

    /**
     * Get max_height
     *
     * @return integer
     */
    public function getMaxHeight()
    {
        return $this->max_height;
    }

    /**
     * Set whether zone is for desktop or not
     *
     * @param string $is_desktop
     * @return BannerZone
     */
    public function setIsDesktop($is_desktop)
    {
        $this->is_desktop = $is_desktop;

        return $this;
    }

    /**
     * Get whether zone is for desktop or not
     *
     * @return string
     */
    public function getIsDesktop()
    {
        return $this->is_desktop;
    }

    /**
     * Set whether zone is for tablet or not
     *
     * @param string $is_tablet
     * @return BannerZone
     */
    public function setIsTablet($is_tablet)
    {
        $this->is_tablet = $is_tablet;

        return $this;
    }

    /**
     * Get whether zone is for tablet or not
     *
     * @return string
     */
    public function getIsTablet()
    {
        return $this->is_tablet;
    }

    /**
     * Set whether zone is for mobile or not
     *
     * @param string $is_mobile
     * @return BannerZone
     */
    public function setIsMobile($is_mobile)
    {
        $this->is_mobile = $is_mobile;

        return $this;
    }

    /**
     * Get whether zone is for mobile or not
     *
     * @return string
     */
    public function getIsMobile()
    {
        return $this->is_mobile;
    }

    /**
     * Set created_at
     *
     * @param integer $created_at
     * @return BannerZone
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

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
     * @param integer $updated_at
     * @return BannerZone
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

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
     * Add banner pages.
     *
     * @param \Fa\Bundle\ContentBundle\Entity\BannerPage $bannerPages
     * @return BannerZone
     */
    public function addBannerPage(\Fa\Bundle\ContentBundle\Entity\BannerPage $bannerPages)
    {
        $this->banner_pages[] = $bannerPages;

        return $this;
    }

    /**
     * Remove banner pages.
     *
     * @param \Fa\Bundle\ContentBundle\Entity\BannerPage $bannerPages
     */
    public function removeBannerPage(\Fa\Bundle\ContentBundle\Entity\BannerPage $bannerPages)
    {
        $this->banner_pages->removeElement($bannerPages);
    }

    /**
     * Get banner pages.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBannerPages()
    {
        return $this->banner_pages;
    }

    /**
     * Get updated_at
     *
     * @return integer
     */
    public function __toString()
    {
        return $this->name;
    }
}
