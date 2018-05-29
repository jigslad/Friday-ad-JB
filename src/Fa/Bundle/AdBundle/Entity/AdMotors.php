<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdMotors
 *
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_motors")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdMotorsRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdMotors
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
     * @ORM\Column(name="manufacturer_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $manufacturer_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="part_manufacturer_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $part_manufacturer_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="make_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $make_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="fuel_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $fuel_type_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="body_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $body_type_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transmission_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $transmission_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="model_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $model_id;

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
     * @ORM\Column(name="berth_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $berth_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="part_of_vehicle_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $part_of_vehicle_id;

    /**
     * @var text
     *
     * @ORM\Column(name="meta_data", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $meta_data;

    /**
     * @var string
     *
     * @ORM\Column(name="old_make", type="string", length=50, nullable=true)
     */
    private $old_make;

    /**
     * @var string
     *
     * @ORM\Column(name="old_model", type="string", length=50, nullable=true)
     */
    private $old_model;

    /**
     * @var string
     *
     * @ORM\Column(name="old_fuel_type", type="string", length=50, nullable=true)
     */
    private $old_fuel_type;

    /**
     * @var string
     *
     * @ORM\Column(name="old_body_type", type="string", length=50, nullable=true)
     */
    private $old_body_type;

    /**
     * @var string
     *
     * @ORM\Column(name="old_transmission", type="string", length=50, nullable=true)
     */
    private $old_transmission;

    /**
     * @var string
     *
     * @ORM\Column(name="old_color", type="string", length=50, nullable=true)
     */
    private $old_color;

    /**
     * @var string
     *
     * @ORM\Column(name="old_tonnage", type="string", length=50, nullable=true)
     */
    private $old_tonnage;

    /**
     * @var string
     *
     * @ORM\Column(name="old_birth", type="string", length=50, nullable=true)
     */
    private $old_birth;

    /**
     * @var integer
     *
     * @ORM\Column(name="part_of_make_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $part_of_make_id;

    /**
     * Get id
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setAd($ad)
    {
        $this->ad = $ad;
        return $this;
    }

    /**
     * Get old make
     *
     * @return string
     */
    public function getOldMake()
    {
        return $this->old_make;
    }

    /**
     * Set old make
     *
     * @param string $old_make
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     *
     */
    public function setOldMake($old_make)
    {
        $this->old_make = $old_make;
        return $this;
    }

    /**
     * Get old model
     *
     * @return string
     */
    public function getOldModel()
    {
        return $this->old_model;
    }

    /**
     * Set old body type
     *
     * @param string old_body_type
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setOldBodyType($old_body_type)
    {
        $this->old_body_type = $old_body_type;
        return $this;
    }

    /**
     * Get old color
     *
     * @return string
     */
    public function getOldColor()
    {
        return $this->old_color;
    }

    /**
     * Set old color
     *
     * @param string old_color
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setOldColor($old_color)
    {
        $this->old_color = $old_color;
        return $this;
    }

    /**
     * Get old fuel type
     *
     * @return string
     */
    public function getOldFuelType()
    {
        return $this->old_fuel_type;
    }

    /**
     * Set old old_fuel_type
     *
     * @param string old_fuel_type
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setOldFuelType($old_fuel_type)
    {
        $this->old_fuel_type = $old_fuel_type;
        return $this;
    }

    /**
     * Get old_transmission
     *
     * @return string
     */
    public function getOldTransmission()
    {
        return $this->old_transmission;
    }

    /**
     * Set old_transmission
     *
     * @param string old_fuel_type
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setOldTransmission($old_transmission)
    {
        $this->old_transmission = $old_transmission;
        return $this;
    }

    /**
     * Get old body type
     *
     * @return string
     */
    public function getOldBodyType()
    {
        return $this->old_body_type;
    }

    /**
     * Set old model
     *
     * @param string $old_model
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setOldModel($old_model)
    {
        $this->old_model = $old_model;
        return $this;
    }

    /**
     * Get make id
     *
     * @return integer
     */
    public function getMakeId()
    {
        return $this->make_id;
    }

    /**
     * Set make id
     *
     * @param integer $make_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setMakeId($make_id)
    {
        $this->make_id = $make_id;
        return $this;
    }

    /**
     * Get fuel type id
     *
     * @return integer
     */
    public function getFuelTypeId()
    {
        return $this->fuel_type_id;
    }

    /**
     * Set fuel type id
     *
     * @param integer $fuel_type_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setFuelTypeId($fuel_type_id)
    {
        $this->fuel_type_id = $fuel_type_id;
        return $this;
    }

    /**
     * Get body type id
     *
     * @return integer
     */
    public function getBodyTypeId()
    {
        return $this->body_type_id;
    }

    /**
     * Set body type id
     *
     * @param integer $body_type_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setBodyTypeId($body_type_id)
    {
        $this->body_type_id = $body_type_id;
        return $this;
    }

    /**
     * Get transmission id
     *
     * @return integer
     */
    public function getTransmissionId()
    {
        return $this->transmission_id;
    }

    /**
     * Set transmission id
     *
     * @param integer $transmission_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setTransmissionId($transmission_id)
    {
        $this->transmission_id = $transmission_id;
        return $this;
    }

    /**
     * Get model id
     *
     * @return integer
     */
    public function getModelId()
    {
        return $this->model_id;
    }

    /**
     * Set model id
     *
     * @param integer $model_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setModelId($model_id)
    {
        $this->model_id = $model_id;
        return $this;
    }

    /**
     * Get color_id
     *
     * @return the integer
     */
    public function getColourId()
    {
        return $this->colour_id;
    }

    /**
     * Set colour_id
     *
     * @param integer $colour_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setColourId($colour_id)
    {
        $this->colour_id = $colour_id;
        return $this;
    }

    /**
     * Get berth id
     *
     * @return integer
     */
    public function getBerthId()
    {
        return $this->berth_id;
    }

    /**
     * Set berth id
     *
     * @param integer $berth_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setBerthId($berth_id)
    {
        $this->berth_id = $berth_id;
        return $this;
    }

    /**
     * Get part of vehicle id
     *
     * @return integer
     */
    public function getPartOfVehicleId()
    {
        return $this->part_of_vehicle_id;
    }

    /**
     * Set part of vehicle id
     *
     * @param integer $part_of_vehicle_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setPartOfVehicleId($part_of_vehicle_id)
    {
        $this->part_of_vehicle_id = $part_of_vehicle_id;
        return $this;
    }

    /**
     * Get part of make id
     *
     * @return integer
     */
    public function getPartOfMakeId()
    {
        return $this->part_of_make_id;
    }

    /**
     * Get part of make id
     *
     * @param integer $part_of_make_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setPartOfMakeId($part_of_make_id)
    {
        $this->part_of_make_id = $part_of_make_id;
        return $this;
    }

    /**
     * Get meta data
     *
     * @return string
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    /**
     * Set meta data
     *
     * @param string $meta_data
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setMetaData($meta_data)
    {
        $this->meta_data = $meta_data;
        return $this;
    }

    /**
     * Get manufacturer_id
     *
     * @return integer
     */
    public function getManufacturerId()
    {
        return $this->manufacturer_id;
    }

    /**
     * Set manufacturer_id
     *
     * @param integer $manufacturer_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setManufacturerId($manufacturer_id)
    {
        $this->manufacturer_id = $manufacturer_id;
        return $this;
    }

    /**
     * Get part_manufacturer_id
     *
     * @return integer
     */
    public function getPartManufacturerId()
    {
        return $this->part_manufacturer_id;
    }

    /**
     * Set part_manufacturer_id
     *
     * @param integer $part_manufacturer_id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMotors
     */
    public function setPartManufacturerId($part_manufacturer_id)
    {
        $this->part_manufacturer_id = $part_manufacturer_id;
        return $this;
    }
}
