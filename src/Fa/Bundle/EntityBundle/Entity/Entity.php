<?php

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\EntityBundle\Entity\Entity
 *
 * This table is used to store various entity or dimension.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="entity", indexes={@ORM\Index(name="fa_entity_entity_name_index", columns={"name"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\EntityRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\EntityTranslation")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\EntityListener" })
 */
class Entity
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
     * @var \Fa\Bundle\EntityBundle\Entity\CategoryDimension
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\CategoryDimension")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_dimension_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $category_dimension;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="min", type="string", length=100, nullable=true)
     */
    private $min;

    /**
     * @var string
     *
     * @ORM\Column(name="max", type="string", length=100, nullable=true)
     */
    private $max;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="seo_value", type="string", length=255, nullable=true)
     */
    private $seo_value;

    /**
     * @var string
     *
     * @ORM\Column(name="url_keys", type="text", nullable=true)
     */
    private $url_keys;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parent_id;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\EntityTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\Category", inversedBy="entities")
     * @ORM\JoinTable(name="entity_category",
     *   joinColumns={
     *     @ORM\JoinColumn(name="entity_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     */
    private $categories;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="ord", type="smallint", nullable=true)
     */
    private $ord;
    
    /**
     * @var string
     *
     * @ORM\Column(name="optional_val", type="string", length=20, nullable=true)
     */
    private $optional_val;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set id
     *
     * @param integer $id
     * @return Entity
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
     * @return Entity
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
     * Set min
     *
     * @param string $min
     * @return Entity
     */
    public function setMin($min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Get min
     *
     * @return string
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set max
     *
     * @param string $max
     * @return Entity
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max
     *
     * @return string
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Add translations
     *
     * @param \Fa\Bundle\EntityBundle\Entity\EntityTranslation $translations
     * @return Entity
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\EntityTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Fa\Bundle\EntityBundle\Entity\EntityTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\EntityTranslation $translations)
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

    /**
     * Add categories
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $categories
     * @return Entity
     */
    public function addCategory(\Fa\Bundle\EntityBundle\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $categories
     */
    public function removeCategory(\Fa\Bundle\EntityBundle\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return AdImage
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
     * Set categoryDimension
     *
     * @param \Fa\Bundle\EntityBundle\Entity\CategoryDimension $categoryDimension
     *
     * @return Entity
     */
    public function setCategoryDimension(\Fa\Bundle\EntityBundle\Entity\CategoryDimension $categoryDimension = null)
    {
        $this->category_dimension = $categoryDimension;

        return $this;
    }

    /**
     * Get categoryDimension
     *
     * @return \Fa\Bundle\EntityBundle\Entity\CategoryDimension
     */
    public function getCategoryDimension()
    {
        return $this->category_dimension;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Entity
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
     * Set seo_value
     *
     * @param string $seo_value
     * @return Entity
     */
    public function setSeoValue($seo_value)
    {
        $this->seo_value = $seo_value;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSeoValue()
    {
        return $this->seo_value;
    }

    /**
     * Set slug
     *
     * @param string $url_keys
     * @return Entity
     */
    public function setUrlKeys($url_keys)
    {
        $this->url_keys = $url_keys;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getUrlKeys()
    {
        return $this->url_keys;
    }

    /**
     * Set ord
     *
     * @param boolean $ord
     * @return Entity
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord
     *
     * @return boolean
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set parent_id.
     *
     * @param integer $parent_id
     * @return Entity
     */
    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;

        return $this;
    }

    /**
     * Get parent_id.
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }
    
    /**
     * Set optional_val
     *
     * @param string $optional_val
     * @return Entity
     */
    public function setOptionalVal($optional_val)
    {
    	$this->optional_val = $optional_val;
    	
    	return $this;
    }
    
    /**
     * Get optional_val
     *
     * @return string
     */
    public function getOptionalVal()
    {
    	return $this->optional_val;
    }
}
