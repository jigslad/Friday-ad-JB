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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="location_group", indexes={@ORM\Index(name="fa_entity_location_group_name_index", columns={"name"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\LocationGroupRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\LocationGroupTranslation")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\LocationGroupListener" })
 * @UniqueEntity(fields={"name", "type"}, message="This location group name already exist in our database.")
 */
class LocationGroup
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
     * Type.
     *
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     * @assert\NotBlank(message="Please select location group type.")
     */
    private $type;

    /**
     * Name.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Please enter location group name.")
     */
    private $name;

    /**
     * Created at.
     *
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * Updated at.
     *
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * Translations.
     *
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\LocationGroupTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * Old ref id.
     *
     * @var integer
     *
     * @ORM\Column(name="old_ref_id", type="integer", nullable=true)
     */
    private $old_ref_id;

    /**
     * Related print edition.
     *
     * @var string
     *
     * @ORM\Column(name="related_print_edition", type="string", length=255, nullable=true)
     */
    private $related_print_edition;

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
     * Constructor.
     */
    public function __construct()
    {
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
     * Set type.
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
     * Get type.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return LocationGroup
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
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return LocationGroup
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated at.
     *
     * @param integer $updatedAt
     *
     * @return LocationGroup
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Add translation.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocationGroupTranslation $translation
     *
     * @return LocationGroup
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\LocationGroupTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocationGroupTranslation $translation
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\LocationGroupTranslation $translation)
    {
        $this->translations->removeElement($translation);
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
     * Set related print edition.
     *
     * @param string $related_print_edition
     *
     * @return LocationGroup
     */
    public function setRelatedPrintEdition($relatedPrintEdition)
    {
        $this->related_print_edition = $relatedPrintEdition;

        return $this;
    }

    /**
     * Get related print edition.
     *
     * @return string
     */
    public function getRelatedPrintEdition()
    {
        return $this->related_print_edition;
    }
}
