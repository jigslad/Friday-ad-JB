<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to information about package rule.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="package_rule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\PackageRuleRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\PromotionBundle\Entity\PackageRuleTranslation")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class PackageRule
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Translatable
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=15, scale=2, nullable=true)
     */
    private $price;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Package
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Package", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $package;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $category;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\LocationGroup
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\LocationGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_group_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $location_group;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\PromotionBundle\Entity\PackageRuleTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set created at value.
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
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
     * Set title
     *
     * @param string $title
     * @return PackageRule
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return PackageRule
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return PackageRule
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return PackageRule
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
     * @return PackageRule
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
     * Set status
     *
     * @param boolean $status
     * @return PackageRule
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
     * Set package
     *
     * @param \Fa\Bundle\UserBundle\Entity\Package $package
     * @return PackageRule
     */
    public function setPackage(\Fa\Bundle\PromotionBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Fa\Bundle\UserBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set category
     *
     * @param \Fa\Bundle\UserBundle\Entity\Category $category
     * @return PackageRule
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\UserBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set location group
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocationGroup $location_group
     * @return PackageRule
     */
    public function setLocationGroup(\Fa\Bundle\EntityBundle\Entity\LocationGroup $location_group = null)
    {
        $this->location_group = $location_group;

        return $this;
    }

    /**
     * Get location group
     *
     * @return \Fa\Bundle\EntityBundle\Entity\LocationGroup
     */
    public function getLocationGroup()
    {
        return $this->location_group;
    }

    /**
     * Add translations
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\PackageRuleTranslation $translations
     * @return PackageRule
     */
    public function addTranslation(\Fa\Bundle\PromotionBundle\Entity\PackageRuleTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\PackageRuleTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\PromotionBundle\Entity\PackageRuleTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
