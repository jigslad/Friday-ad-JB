<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdAnimals
 *
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_animals")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdAnimalsRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdAnimals
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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $ad_type_id;

    /**
     * @var string
     *
     * @ORM\Column(name="gender_id", type="string", length=120, nullable=true)
     * @Gedmo\Versioned
     */
    private $gender_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="colour_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $colour_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="breed_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $breed_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="species_id", type="integer", length=10, nullable=true)
     */
    private $species_id;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_data", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $meta_data;

    /**
     * get id
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set id
     *
     * @param integer $id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setAd($ad)
    {
        $this->ad = $ad;
        return $this;
    }

    /**
     * get ad type id
     *
     * @return number
     */
    public function getAdTypeId()
    {
        return $this->ad_type_id;
    }

    /**
     * set ad type id
     *
     * @param integer $ad_type_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setAdTypeId($ad_type_id)
    {
        $this->ad_type_id = $ad_type_id;
        return $this;
    }

    /**
     * get gender id
     *
     * @return string
     */
    public function getGenderId()
    {
        return $this->gender_id;
    }

    /**
     * set gender id
     *
     * @param string $gender_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setGenderId($gender_id)
    {
        $this->gender_id = $gender_id;
        return $this;
    }

    /**
     * get colour id
     *
     * @return number
     */
    public function getColourId()
    {
        return $this->colour_id;
    }

    /**
     * set colour id
     *
     * @param integer $colour_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setColourId($colour_id)
    {
        $this->colour_id = $colour_id;
        return $this;
    }

    /**
     * get breed id
     *
     * @return number
     */
    public function getBreedId()
    {
        return $this->breed_id;
    }

    /**
     * set breed id
     *
     * @param integer $breed_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setBreedId($breed_id)
    {
        $this->breed_id = $breed_id;
        return $this;
    }

    /**
     * get species id
     *
     * @return number
     */
    public function getSpeciesId()
    {
        return $this->species_id;
    }

    /**
     * set species id
     *
     * @param integer $species_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setSpeciesId($species_id)
    {
        $this->species_id = $species_id;
        return $this;
    }

    /**
     * get meta data
     *
     * @return string
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    /**
     * set meta data
     *
     * @param string $meta_data
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setMetaData($meta_data)
    {
        $this->meta_data = $meta_data;
        return $this;
    }
}
