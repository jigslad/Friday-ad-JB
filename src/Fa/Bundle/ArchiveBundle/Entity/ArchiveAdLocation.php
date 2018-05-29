<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ArchiveBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="archive_ad_location", indexes={@Index(name="post_code_iad", columns={"postcode"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ArchiveBundle\Repository\ArchiveAdLocationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ArchiveAdLocation
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
     */
    private $postcode;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", precision=21, scale=8, nullable=true)
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", precision=21, scale=8, nullable=true)
     */
    private $longitude;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_country;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domicile_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_domicile;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="town_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_town;

    /**
     * @var \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ArchiveBundle\Entity\ArchiveAd", inversedBy="ad_locations")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
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
     * @return Entity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set postcode.
     *
     * @param string $postcode
     *
     * @return ArchiveAdLocation
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode.
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set latitude.
     *
     * @param float $latitude
     *
     * @return ArchiveAdLocation
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude.
     *
     * @param float $longitude
     *
     * @return ArchiveAdLocation
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return ArchiveAdLocation
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
     * @return ArchiveAdLocation
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
     * Set location country.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationCountry
     *
     * @return ArchiveAdLocation
     */
    public function setLocationCountry(\Fa\Bundle\EntityBundle\Entity\Location $locationCountry = null)
    {
        $this->location_country = $locationCountry;

        return $this;
    }

    /**
     * Get location country.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationCountry()
    {
        return $this->location_country;
    }

    /**
     * Set location domicile.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationDomicile
     *
     * @return ArchiveAdLocation
     */
    public function setLocationDomicile(\Fa\Bundle\EntityBundle\Entity\Location $locationDomicile = null)
    {
        $this->location_domicile = $locationDomicile;

        return $this;
    }

    /**
     * Get location domicile.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationDomicile()
    {
        return $this->location_domicile;
    }

    /**
     * Set location town.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationTown
     *
     * @return ArchiveAdLocation
     */
    public function setLocationTown(\Fa\Bundle\EntityBundle\Entity\Location $locationTown = null)
    {
        $this->location_town = $locationTown;

        return $this;
    }

    /**
     * Get location town.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationTown()
    {
        return $this->location_town;
    }

    /**
     * Set ad.
     *
     * @param \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd $ad
     *
     * @return ArchiveAdLocation
     */
    public function setAd(\Fa\Bundle\ArchiveBundle\Entity\ArchiveAd $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad.
     *
     * @return \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd
     */
    public function getAd()
    {
        return $this->ad;
    }
}
