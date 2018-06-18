<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\Ad
 *
 * This table is used to store ad information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_location", indexes={@Index(name="post_code_iad", columns={"postcode"}), @ORM\Index(name="fa_ad_location_trans_id",  columns={"trans_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdLocationRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdLocation
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
     * @ORM\Column(name="postcode", type="string", length=20, nullable=true)
     * @Gedmo\Versioned
     */
    private $postcode;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", precision=21, scale=8, nullable=true)
     * @Gedmo\Versioned
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", precision=21, scale=8, nullable=true)
     * @Gedmo\Versioned
     */
    private $longitude;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $location_country;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domicile_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $location_domicile;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="town_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $location_town;
    
    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="area_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $location_area;
    
    

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Locality
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Locality")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="locality_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $locality;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad", inversedBy="ad_locations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

    /**
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=50, nullable=true)
     */
    private $update_type;

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
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
     * Set postcode
     *
     * @param string $postcode
     *
     * @return AdLocation
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     *
     * @return AdLocation
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     *
     * @return AdLocation
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return AdLocation
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
     * @return AdLocation
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
     * Set locationCountry
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationCountry
     *
     * @return AdLocation
     */
    public function setLocationCountry(\Fa\Bundle\EntityBundle\Entity\Location $locationCountry = null)
    {
        $this->location_country = $locationCountry;

        return $this;
    }

    /**
     * Get locationCountry
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationCountry()
    {
        return $this->location_country;
    }

    /**
     * Set locationDomicile
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationDomicile
     *
     * @return AdLocation
     */
    public function setLocationDomicile(\Fa\Bundle\EntityBundle\Entity\Location $locationDomicile = null)
    {
        $this->location_domicile = $locationDomicile;

        return $this;
    }

    /**
     * Get locationDomicile
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationDomicile()
    {
        return $this->location_domicile;
    }

    /**
     * Set locationTown
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationTown
     *
     * @return AdLocation
     */
    public function setLocationTown(\Fa\Bundle\EntityBundle\Entity\Location $locationTown = null)
    {
        $this->location_town = $locationTown;

        return $this;
    }

    /**
     * Get locationTown
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationTown()
    {
        return $this->location_town;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return AdLocation
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
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
     * Set trans_id
     *
     * @param string $trans_id
     *
     * @return AdLocation
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;

        return $this;
    }

    /**
     * Get trans_id
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * Set update_type
     *
     * @param string $update_type
     * @return Ad
     */
    public function setUpdateType($update_type)
    {
        $this->update_type = $update_type;

        return $this;
    }

    /**
     * Get update_type
     *
     * @return string
     */
    public function getUpdateType()
    {
        return $this->update_type;
    }

    /**
     * Set Locality
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Locality $locality
     *
     * @return AdLocation
     */
    public function setLocality(\Fa\Bundle\EntityBundle\Entity\Locality $locality = null)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * Get Locality
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Locality
     */
    public function getLocality()
    {
        return $this->locality;
    }
    
    /**
     * Set locationArea
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationArea
     *
     * @return AdLocation
     */
    public function setLocationArea(\Fa\Bundle\EntityBundle\Entity\Location $locationArea= null)
    {
    	$this->location_area = $locationArea;
    	
    	return $this;
    }
    
    /**
     * Get LocationArea
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationArea()
    {
    	return $this->location_area;
    }
   
       
}
