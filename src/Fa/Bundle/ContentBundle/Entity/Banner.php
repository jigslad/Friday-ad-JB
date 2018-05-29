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
 * @ORM\Table(name="banner")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\BannerRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\BannerListener" })
 * @ORM\HasLifecycleCallbacks
 */
class Banner
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
     * @ORM\Column(name="code", type="text")
     * @Assert\NotBlank(message="Code is required.")
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var \Fa\Bundle\ContentBundle\Entity\BannerZone
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ContentBundle\Entity\BannerZone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="banner_zone_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Assert\NotBlank(message="Banner zone is required.")
     */
    private $banner_zone;

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
     * @ORM\JoinTable(name="banner_banner_page",
     *   joinColumns={
     *     @ORM\JoinColumn(name="banner_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="banner_page_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     */
    private $banner_pages;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $category;

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
     * Set code
     *
     * @param string $code
     * @return Banner
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Banner
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set banner_zone
     *
     * @param \Fa\Bundle\ContentBundle\Entity\BannerZone $banner_zone
     * @return Banner
     */
    public function setBannerZone(\Fa\Bundle\ContentBundle\Entity\BannerZone $banner_zone = null)
    {
        $this->banner_zone = $banner_zone;

        return $this;
    }

    /**
     * Get banner_zone
     *
     * @return \Fa\Bundle\ContentBundle\Entity\BannerZone
     */
    public function getBannerZone()
    {
        return $this->banner_zone;
    }

    /**
     * Set created_at
     *
     * @param integer $created_at
     * @return Banner
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
     * @return Banner
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
     * @return Banner
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
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return Ad
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
