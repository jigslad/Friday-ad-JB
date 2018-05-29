<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_property")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdPropertyRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdProperty
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
     * @ORM\Column(name="number_of_bedrooms_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $number_of_bedrooms_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="room_size_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $room_size_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="amenities_id", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $amenities_id;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_data", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $meta_data;

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
     * Get ad.
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set ad.
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdAnimals
     */
    public function setAd($ad)
    {
        $this->ad = $ad;
        return $this;
    }

    /**
     * Set number_of_bedrooms_id.
     *
     * @param integer $number_of_bedrooms_id
     *
     * @return AdProperty
     */
    public function setNumberOfBedroomsId($number_of_bedrooms_id)
    {
        $this->number_of_bedrooms_id = $number_of_bedrooms_id;

        return $this;
    }

    /**
     * Get number_of_bedrooms_id.
     *
     * @return integer
     */
    public function getNumberOfBedroomsId()
    {
        return $this->number_of_bedrooms_id;
    }

    /**
     * Set amenities.
     *
     * @param string $amenities_id
     *
     * @return AdProperty
     */
    public function setAmenitiesId($amenities_id)
    {
        $this->amenities_id = $amenities_id;

        return $this;
    }

    /**
     * Get amenities.
     *
     * @return integer
     */
    public function getAmenitiesId()
    {
        return $this->amenities_id ;
    }

    /**
     * Set room size.
     *
     * @param integer $room_size_id
     *
     * @return AdProperty
     */
    public function setRoomSizeId($room_size_id)
    {
        $this->room_size_id = $room_size_id;

        return $this;
    }

    /**
     * Get bedroom id.
     *
     * @return integer
     */
    public function getRoomSizeId()
    {
        return $this->room_size_id ;
    }

    /**
     * Get meta data.
     *
     * @return string
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    /**
     * Set meta data.
     *
     * @param string $meta_data
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdProperty
     */
    public function setMetaData($meta_data)
    {
        $this->meta_data = $meta_data;
        return $this;
    }
}
