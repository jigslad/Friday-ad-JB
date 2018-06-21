<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * This table is used to store locality data.
 *
 * @author GauravAggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="location_postal", indexes={@Index(name="fa_postal_code", columns={"postal_code"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\LocationPostalRepository")
 */
class LocationPostal
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
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=10)
     */
    private $postal_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="location_id", type="integer", nullable=true)
     */
    private $location_id;
    

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
     * Set id.
     *
     * @param integer $id
     *
     * @return LocationPostal
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set location_id.
     *
     * @param integer $location_id
     * @return LocationPostal
     */
    public function setLocationId($location_id)
    {
    	$this->location_id= $location_id;

        return $this;
    }

    /**
     * Get location_id.
     *
     * @return integer
     */
    public function getLocationId()
    {
    	return $this->location_id;
    }

    /**
     * Set postal_code.
     *
     * @param string $postal_code
     *
     * @return LocationPostal
     */
    public function setPostalCode($postal_code)
    {
    	$this->postal_code= $postal_code;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getPostalCode()
    {
    	return $this->postal_code;
    }

}
