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
use Doctrine\ORM\Mapping\Index as Index;

/**
 * This table is used to store locality data.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="locality", indexes={@Index(name="fa_locality_old_id", columns={"old_id"}), @Index(name="fa_locality_text", columns={"locality_text"}), @Index(name="fa_locality_url", columns={"url"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\LocalityRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\LocalityTranslation")
 */
class Locality
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
     * Dependent locality.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * Double dependent locality.
     *
     * @var string
     *
     * @ORM\Column(name="locality_text", type="string", length=100)
     */
    private $locality_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="town_id", type="integer", nullable=true)
     */
    private $town_id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=150, nullable=true)
     */
    private $url;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_id", type="integer", nullable=true)
     */
    private $old_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ref_id", type="integer", nullable=true)
     */
    private $ref_id;

    /**
     * Translations.
     *
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\LocalityTranslation", mappedBy="object", cascade={"persist","remove"})
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
     * @return Locality
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set town_id.
     *
     * @param integer $town_id
     * @return Dotmailer
     */
    public function setTownId($town_id)
    {
        $this->town_id = $town_id;

        return $this;
    }

    /**
     * Get town_id.
     *
     * @return integer
     */
    public function getTownId()
    {
        return $this->town_id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return StaticPage
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
     * Get locality text.
     *
     * @return string
     */
    public function getLocalityText()
    {
        return $this->locality_text;
    }

    /**
     * Set locality text
     *
     * @param string locality_txt
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Locality
     */
    public function setLocalityText($locality_text)
    {
        $this->locality_text = $locality_text;
        return $this;
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
     * Add translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocalityTranslation $translations
     *
     * @return Locality
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\LocalityTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocalityTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\LocalityTranslation $translations)
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
