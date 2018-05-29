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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store upsell information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="upsell")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\UpsellRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\PromotionBundle\Entity\UpsellTranslation")
 * @UniqueEntity(fields="title", message="This upsell name already exist in our database.")
 * @ORM\HasLifecycleCallbacks
 */
class Upsell
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
     * @ORM\Column(name="type", type="integer")
     * @assert\NotBlank(message="Please select upsell type.")
     */
    private $type;
    
    /**
     * @var string
     *
     * @ORM\Column(name="upsell_for", type="string", length=255, nullable=true)
     */
    private $upsell_for;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Please enter upsell title.")
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
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=20, nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="value1", type="string", length=20, nullable=true)
     */
    private $value1;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=10, nullable=true)
     * @Assert\Regex(pattern="/^[a-z0-9 ]+$/i", message="The value {{ value }} is not a valid alpha numeric.")
     * @Assert\Length(max = 8, maxMessage = "Duration cannot be longer than {{ limit }} characters long.")
     */
    private $duration;

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
     * @ORM\OneToMany(targetEntity="Fa\Bundle\PromotionBundle\Entity\UpsellTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @assert\NotBlank(message="Please select upsell status.")
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
     * Set id
     *
     * @param integer $id
     *
     * @return Upsell
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set type
     *
     * @param integer $type
     * @return Upsell
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Upsell
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
     * @return Upsell
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
     * @return Upsell
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
     * Set value
     *
     * @param string $value
     * @return Upsell
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
     * Set value1
     *
     * @param string $value1
     * @return Upsell
     */
    public function setValue1($value1)
    {
        $this->value1 = $value1;

        return $this;
    }

    /**
     * Get value1
     *
     * @return string
     */
    public function getValue1()
    {
        return $this->value1;
    }

    /**
     * Set duration
     *
     * @param string duration
     * @return Upsell
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }
    
    /**
     * Set upsell for
     *
     * @param string upsell_for
     * @return Upsell
     */
    public function setUpsellFor($upsell_for)
    {
        $this->upsell_for = $upsell_for;
    
        return $this;
    }
    
    /**
     * Get upsell_for
     *
     * @return string
     */
    public function getUpsellFor()
    {
        return $this->upsell_for;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Upsell
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
     * @return Upsell
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
     * @return Upsell
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
     * Add translations
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\UpsellTranslation $translations
     * @return Upsell
     */
    public function addTranslation(\Fa\Bundle\PromotionBundle\Entity\UpsellTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\UpsellTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\PromotionBundle\Entity\UpsellTranslation $translations)
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
