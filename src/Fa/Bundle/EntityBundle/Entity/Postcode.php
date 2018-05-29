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
 * This table is used to store postcode data.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="postcode" ,indexes={@Index(name="post_code_i", columns={"post_code"}), @ORM\Index(name="post_code_c_i", columns={"post_code_c"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\PostcodeRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\PostcodeListener" })
 */
class Postcode
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
     * @ORM\Column(name="post_code", type="string", length=50)
     */
    private $post_code;

    /**
     * @var string
     *
     * @ORM\Column(name="post_code_c", type="string", length=50, nullable=true)
     */
    private $post_code_c;

    /**
     * @var integer
     *
     * @ORM\Column(name="easting", type="integer", nullable=true)
     */
    private $easting;

    /**
     * @var integer
     *
     * @ORM\Column(name="northing", type="integer", nullable=true)
     */
    private $northing;

    /**
     * @var integer
     *
     * @ORM\Column(name="latitude", type="decimal", nullable=true, precision=12, scale=8)
     */
    private $latitude;

    /**
     * @var integer
     *
     * @ORM\Column(name="longitude", type="decimal", nullable=true, precision=12, scale=8)
     */
    private $longitude;

    /**
     * @var integer
     *
     * @ORM\Column(name="locality_id", type="integer", nullable=true)
     */
    private $locality_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="town_id", type="integer", nullable=true)
     */
    private $town_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="county_id", type="integer", nullable=true)
     */
    private $county_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ref_id", type="integer", nullable=true)
     */
    private $ref_id;

    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * Get id.
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get post code.
     *
     * @return string
     */
    public function getPostCode()
    {
        return $this->post_code;
    }

    /**
     * Set post code.
     *
     * @param unknown $post_code
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setPostCode($post_code)
    {
        $this->post_code = $post_code;
        return $this;
    }

    /**
     * Get post codeC.
     *
     * @return string
     */
    public function getPostCodeC()
    {
        return $this->post_code_c;
    }

    /**
     * Set post codeC.
     *
     * @param string $post_code_c
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setPostCodeC($post_code_c)
    {
        $this->post_code_c = $post_code_c;
        return $this;
    }

    /**
     * Get easting.
     *
     * @return number
     */
    public function getEasting()
    {
        return $this->easting;
    }

    /**
     * Set easting.
     *
     * @param string $easting
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setEasting($easting)
    {
        $this->easting = $easting;
        return $this;
    }

    /**
     * Get northing.
     *
     * @return number
     */
    public function getNorthing()
    {
        return $this->northing;
    }

    /**
     * Set northing.
     *
     * @param string $northing
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setNorthing($northing)
    {
        $this->northing = $northing;
        return $this;
    }

    /**
     * Get latitude.
     *
     * @return number
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set latitude.
     *
     * @param string $latitude
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * Get longitude.
     *
     * @return number
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set longitude.
     *
     * @param string $longitude
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * Get locality id.
     *
     * @return number
     */
    public function getLocalityId()
    {
        return $this->locality_id;
    }

    /**
     * Set locality id.
     *
     * @param string $locality_id
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setLocalityId($locality_id)
    {
        $this->locality_id = $locality_id;
        return $this;
    }

    /**
     * Get town id.
     *
     * @return number
     */
    public function getTownId()
    {
        return $this->town_id;
    }

    /**
     * Set town id.
     *
     * @param string $town_id
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setTownId($town_id)
    {
        $this->town_id = $town_id;
        return $this;
    }

    /**
     * Get county id.
     *
     * @return number
     */
    public function getCountyId()
    {
        return $this->county_id;
    }

    /**
     * Set county id.
     *
     * @param string $county_id
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setCountyId($county_id)
    {
        $this->county_id = $county_id;
        return $this;
    }

    /**
     * Get ref id.
     *
     * @return number
     */
    public function getRefId()
    {
        return $this->ref_id;
    }

    /**
     * Set ref id.
     *
     * @param string $county_id
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Postcode
     */
    public function setRefId($county_id)
    {
        $this->ref_id = $county_id;
        return $this;
    }

    /**
     * Set street
     *
     * @param string $street
     *
     * @return Postcode
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

}

