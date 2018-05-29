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

/**
 * This table is used to store location gruop location information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="location_group_location")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\LocationGroupLocationRepository")
 */
class LocationGroupLocation
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
     * Location group.
     *
     * @var \Fa\Bundle\EntityBundle\Entity\LocationGroup
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\LocationGroup",cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_group_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_group;

    /**
     * Location country.
     *
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_country;

    /**
     * Location domicile.
     *
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domicile_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_domicile;

    /**
     * Location town.
     *
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="town_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_town;

    /**
     * Old ref id.
     *
     * @var integer
     *
     * @ORM\Column(name="old_ref_id", type="integer", nullable=true)
     */
    private $old_ref_id;

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
     * @return Entity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set location country.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationCountry
     *
     * @return LocationGroupLocation
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
     * @return LocationGroupLocation
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
     * @return LocationGroupLocation
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
     * Set location group.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\LocationGroup $location_group
     *
     * @return LocationGroupLocation
     */
    public function setLocationGroup(\Fa\Bundle\EntityBundle\Entity\LocationGroup $location_group = null)
    {
        $this->location_group = $location_group;

        return $this;
    }

    /**
     * Get location group.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\LocationGroup
     */
    public function getLocationGroup()
    {
        return $this->location_group;
    }
}
