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
 * This table is used to store throughfare.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="throughfare")
 * @ORM\Entity()
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\ThroughfareTranslation")
 */
class Throughfare
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
     * Post code.
     *
     * @var string
     *
     * @ORM\Column(name="post_code", type="string", length=50)
     */
    private $post_code;


    /**
     * Dependent thorofare.
     *
     * @var string
     *
     * @ORM\Column(name="dependentThorofare", type="string", length=100)
     * @Gedmo\Translatable
     */
    private $dependentThorofare;

    /**
     * Double dependent throughfare.
     *
     * @var string
     *
     * @ORM\Column(name="doubleDependentThroughfare", type="string", length=100)
     * @Gedmo\Translatable
     */
    private $doubleDependentThroughfare;

    /**
     * Translations.
     *
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\ThroughfareTranslation", mappedBy="object", cascade={"persist","remove"})
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
     * @return Throughfare
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set dependentThorofare
     *
     * @param string $dependentThorofare
     *
     * @return Throughfare
     */
    public function setDependentThroughfare($dependentThorofare)
    {
        $this->dependentThorofare = $dependentThorofare;

        return $this;
    }

    /**
     * Get dependent thorofare.
     *
     * @return string
     */
    public function getDependentThroughfare()
    {
        return $this->dependentThorofare;
    }

    /**
     * Set double dependent throughfare.
     *
     * @param string $doubleDependentThroughfare
     *
     * @return Throughfare
     */
    public function setDoubleDependentThroughfare($doubleDependentThroughfare)
    {
        $this->doubleDependentThroughfare = $doubleDependentThroughfare;

        return $this;
    }

    /**
     * Get double dependent throughfare.
     *
     * @return string
     */
    public function getDoubleDependentThroughfare()
    {
        return $this->doubleDependentThroughfare;
    }

    /**
     * Add translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\ThroughfareTranslation $translations
     *
     * @return Throughfare
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\ThroughfareTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\ThroughfareTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\ThroughfareTranslation $translations)
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
}
