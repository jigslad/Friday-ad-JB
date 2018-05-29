<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to to store delivery method option.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="delivery_method_option")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOptionTranslation")
 * @UniqueEntity(fields="name", message="This name already exist in our database.")
 * @ORM\EntityListeners({ "Fa\Bundle\PaymentBundle\Listener\DeliveryMethodOptionListener" })
 * @ORM\HasLifecycleCallbacks
 */
class DeliveryMethodOption
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Please enter name.")
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="cost", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Cost cannot be longer than {{ limit }} digits long.")
     */
    private $cost;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @assert\NotBlank(message="Please select status.")
     */
    private $status;

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
     * @ORM\OneToMany(targetEntity="Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOptionTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

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
     *
     * @return DeliveryMethodOptions
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
     * Set cost
     *
     * @param float $cost
     *
     * @return DeliveryMethodOptions
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return DeliveryMethodOptions
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param integer $updatedAt
     *
     * @return DeliveryMethodOptions
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
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
     * @param Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOptionTranslation $translations
     * @return Upsell
     */
    public function addTranslation(\Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOptionTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOptionTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOptionTranslation $translations)
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
