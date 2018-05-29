<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdServices
 *
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_services")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdServicesRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdServices
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
     * @ORM\Column(name="service_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $service_type_id;

    /**
     * @var string
     *
     * @ORM\Column(name="services_offered_id", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $services_offered_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="event_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $event_type_id;

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
     * Set service_type_id
     *
     * @param integer $service_type_id
     * @return AdServices
     */
    public function setServiceTypeId($service_type_id)
    {
        $this->service_type_id = $service_type_id;

        return $this;
    }

    /**
     * Get $service_type_id
     *
     * @return integer
     */
    public function getServiceTypeId()
    {
        return $this->service_type_id ;
    }

    /**
     * Set services_offered_id
     *
     * @param integer $services_offered_id
     * @return AdServices
     */
    public function setServicesOfferedId($services_offered_id)
    {
        $this->services_offered_id = $services_offered_id;

        return $this;
    }

    /**
     * Get $services_offered_id
     *
     * @return string
     */
    public function getServicesOfferedId()
    {
        return $this->services_offered_id ;
    }

    /**
     * Set event_type_id
     *
     * @param integer $event_type_id
     * @return AdServices
     */
    public function setEventTypeId($event_type_id)
    {
        $this->event_type_id = $event_type_id;

        return $this;
    }

    /**
     * Get $event_type_id
     *
     * @return integer
     */
    public function getEventTypeId()
    {
        return $this->event_type_id ;
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
