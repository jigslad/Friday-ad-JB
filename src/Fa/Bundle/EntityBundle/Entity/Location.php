<?php

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\EntityBundle\Entity\Location
 *
 * This table is used to store location information (country, region, domicile, town, zip etc...)
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="location", indexes={@ORM\Index(name="fa_entity_location_name_index", columns={"name"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\LocationRepository")
 * @Gedmo\Tree(type="nested")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\LocationTranslation")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\LocationListener" })
 * @UniqueEntity(fields={"parent", "name"}, errorPath="name", message="This location name already exist in our database.")
 */
class Location
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
     * @ORM\Column(name="name", type="string", length=100)
     * @assert\NotBlank(message="Location name is required.")
     * @Assert\Regex(pattern="/^[a-z0-9- ]+$/i", message="Please enter alpha numeric value only.")
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="decimal", precision=12, scale=8, nullable=true)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="decimal", precision=12, scale=8, nullable=true)
     */
    private $longitude;

    /**
     * @var integer
     *
     * @ORM\Column(name="lft", type="integer")
     * @Gedmo\TreeLeft
     */
    private $lft;

    /**
     * @var integer
     *
     * @ORM\Column(name="rgt", type="integer")
     * @Gedmo\TreeRight
     */
    private $rgt;

    /**
     * @var integer
     *
     * @ORM\Column(name="root", type="integer")
     * @Gedmo\TreeRoot
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="lvl", type="integer")
     * @Gedmo\TreeLevel
     */
    private $lvl;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\Location", mappedBy="parent")
     * @ORM\OrderBy({
     *     "lft"="ASC"
     * })
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\LocationTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\TreeParent
     */
    private $parent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_ref_id", type="integer", nullable=true)
     */
    private $old_ref_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="region_id", type="integer", nullable=true)
     */
    private $region_id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=150, nullable=true)
     */
    private $url;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_special_area", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_special_area;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_url", type="string", length=150, nullable=true)
     */
    private $redirect_url;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Location
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
     * Set url
     *
     * @param string $url
     * @return Location
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     * @return Location
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return Location
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Location
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Location
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Location
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return Location
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Set old ref
     *
     * @param integer $old_ref_id
     * @return Location
     */
    public function setOldRefId($old_ref_id)
    {
        $this->old_ref_id = $old_ref_id;

        return $this;
    }

    /**
     * Set region id
     *
     * @param integer $region_id
     * @return Location
     */
    public function setRegionId($region_id)
    {
        $this->region_id = $region_id;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Add children
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $children
     * @return Location
     */
    public function addChild(\Fa\Bundle\EntityBundle\Entity\Location $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $children
     */
    public function removeChild(\Fa\Bundle\EntityBundle\Entity\Location $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add translations
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocationTranslation $translations
     * @return Location
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\LocationTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocationTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\LocationTranslation $translations)
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
     * Set parent
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $parent
     * @return Location
     */
    public function setParent(\Fa\Bundle\EntityBundle\Entity\Location $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getParent()
    {
        return $this->parent;
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
     * Get payment
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get oldRefId
     *
     * @return integer
     */
    public function getOldRefId()
    {
        return $this->old_ref_id;
    }

    /**
     * Get regionId
     *
     * @return integer
     */
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * Set redirect_url
     *
     * @param string $redirect_url
     * @return Location
     */
    public function setRedirectUrl($redirect_url)
    {
        $this->redirect_url = $redirect_url;

        return $this;
    }

    /**
     * Get redirect_url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirect_url;
    }
    
    /**
     * Set is_special_area
     *
     * @param boolean $status
     * @return Location
     */
    public function setIsSpecialArea($is_special_area)
    {
    	$this->is_special_area = $is_special_area;
    	
    	return $this;
    }
    
    /**
     * Get is_special_area
     *
     * @return Location
     */
    public function getIsSpecialArea()
    {
    	return $this->is_special_area;
    }
}
