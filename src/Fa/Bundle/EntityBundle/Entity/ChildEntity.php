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
  This table is used to store information about child entity.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="child_entity", indexes={@ORM\Index(name="fa_entity_child_entity_name_index", columns={"name"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\ChildEntityRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\ChildEntityTranslation")
 */
class ChildEntity
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
     */
    private $type;

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
     * Translations.
     *
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\ChildEntityTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * Entity.
     *
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entity_id", referencedColumnName="id")
     * })
     */
    private $entity;

    /**
     * Status.
     *
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

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
     * Set type.
     *
     * @param integer $type
     *
     * @return ChildEntity
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
     * @return ChildEntity
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
     * @return ChildEntity
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
     * Add translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\ChildEntityTranslation $translations
     *
     * @return ChildEntity
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\ChildEntityTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\ChildEntityTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\ChildEntityTranslation $translations)
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
     * Set entity.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $entity
     *
     * @return ChildEntity
     */
    public function setEntity(\Fa\Bundle\EntityBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
