<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store various category dimension.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="category_dimension")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\CategoryDimensionTranslation")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\CategoryDimensionListener" })
 */
class CategoryDimension
{
    /**
     * Id.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Name.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * Entity id.
     *
     * @var string
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     */
    private $entity_id;

    /**
     * Is index.
     *
     * @var boolean
     *
     * @ORM\Column(name="is_index", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_index;

    /**
     * Is searchable
     *
     * @var boolean
     *
     * @ORM\Column(name="is_searchable", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_searchable;

    /**
     * Search Type.
     *
     * @var string
     *
     * @ORM\Column(name="search_type", type="string", length=100)
     */
    private $search_type;

    /**
     * Category.
     *
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $category;

    /**
     * Status.
     *
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * Translations.
     *
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\CategoryDimensionTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Entity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CategoryDimension
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
     * Set status.
     *
     * @param boolean $status
     *
     * @return CategoryDimension
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set category.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     *
     * @return CategoryDimension
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Add translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\CategoryDimensionTranslation $translations
     *
     * @return Entity
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\CategoryDimensionTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\CategoryDimensionTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\CategoryDimensionTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * get is index
     *
     * @return boolean
     */
    public function getIsIndex()
    {
        return $this->is_index;
    }

    /**
     * set is index
     *
     * @param integer $is_index
     *
     * @return \Fa\Bundle\EntityBundle\Entity\CategoryDimension
     */
    public function setIsIndex($is_index)
    {
        $this->is_index = $is_index;
        return $this;
    }

    /**
     * get is searchable
     *
     * @return boolean
     */
    public function getIsSearchable()
    {
        return $this->is_searchable;
    }

    /**
     * set is searchable
     *
     * @param integer $is_searchable
     *
     * @return \Fa\Bundle\EntityBundle\Entity\CategoryDimension
     */
    public function setIsSearchable($is_searchable)
    {
        $this->is_searchable = $is_searchable;
        return $this;
    }

    /**
     * get search type
     *
     * @return string
     */
    public function getSearchType()
    {
        return $this->search_type;
    }

    /**
     * set search type
     *
     * @param string $search_type
     *
     * @return \Fa\Bundle\EntityBundle\Entity\CategoryDimension
     */
    public function setSearchType($search_type)
    {
        $this->search_type = $search_type;
        return $this;
    }
}
