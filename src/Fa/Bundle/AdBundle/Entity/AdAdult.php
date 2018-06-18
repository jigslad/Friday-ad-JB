<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdAdult
 *
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_adult")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdAdultRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdAdult
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
     * @var string
     *
     * @ORM\Column(name="services_id", type="string", length=500, nullable=true)
     * @Gedmo\Versioned
     */
    private $services_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="independent_or_agency_id", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $independent_or_agency_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="travel_arrangements_id", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $travel_arrangements_id;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_data", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $meta_data;
    
    /**
     * @var string
     *
     * @ORM\Column(name="gender_id", type="string", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $gender_id;


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

    /**
     * Set services_id.
     *
     * @param string $services_id
     * @return AdAdult
     */
    public function setServicesId($services_id)
    {
        $this->services_id = $services_id;

        return $this;
    }

    /**
     * Get services_id.
     *
     * @return string
     */
    public function getServicesId()
    {
        return $this->services_id;
    }

    /**
     * Set independent_or_agency_id.
     *
     * @param string $independent_or_agency_id
     * @return AdAdult
     */
    public function setIndependentOrAgencyId($independent_or_agency_id)
    {
        $this->independent_or_agency_id = $independent_or_agency_id;

        return $this;
    }

    /**
     * Get independent_or_agency_id.
     *
     * @return string
     */
    public function getIndependentOrAgencyId()
    {
        return $this->independent_or_agency_id;
    }

    /**
     * Set travel_arrangements_id.
     *
     * @param string $travel_arrangements_id
     * @return AdAdult
     */
    public function setTravelArrangementsId($travel_arrangements_id)
    {
        $this->travel_arrangements_id = $travel_arrangements_id;

        return $this;
    }

    /**
     * Get travel_arrangements_id.
     *
     * @return string
     */
    public function getTravelArrangementsId()
    {
        return $this->travel_arrangements_id;
    }
    
    /**
     * set gender id
     *
     * @param string $gender_id
     *
     * @return AdAdult
     */
    public function setGenderId($gender_id)
    {
    	$this->gender_id = $gender_id;
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
    
}
