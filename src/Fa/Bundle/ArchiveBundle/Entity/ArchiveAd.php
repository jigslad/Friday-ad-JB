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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="archive_ad", indexes={@ORM\Index(name="fa_archive_archive_ad_archived_at_index", columns={"archived_at"}), @ORM\Index(name="fa_archive_archive_ad_ad_view_counter_index", columns={"ad_view_counter"}), @ORM\Index(name="fa_archive_archive_ad_email_index", columns={"email"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\ArchiveBundle\Repository\ArchiveAdRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ArchiveAd
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
     * @var \Fa\Bundle\AdBundle\Entity\AdMain
     *
     * @ORM\OneToOne(targetEntity="Fa\Bundle\AdBundle\Entity\AdMain")
     * @ORM\JoinColumn(name="ad_main", referencedColumnName="id", onDelete="CASCADE")
     */
    private $ad_main;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\ArchiveBundle\Entity\ArchiveAdLocation", mappedBy="ad", cascade={"persist","remove"})
     */
    private $ad_locations;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", length=10, nullable=true)
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_data", type="text", nullable=true)
     */
    private $ad_data;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_vertical_data", type="text", nullable=true)
     */
    private $ad_vertical_data;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_location_data", type="text", nullable=true)
     */
    private $ad_location_data;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_moderate_data", type="text", nullable=true)
     */
    private $ad_moderate_data;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_view_counter", type="integer", length=10)
     */
    private $ad_view_counter;

    /**
     * @var integer
     *
     * @ORM\Column(name="archived_at", type="integer", length=10)
     */
    private $archived_at;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ad_locations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return ArchiveAd
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set ad main.
     *
     * @param \Fa\Bundle\AdBundle\Entity\AdMain $adMain
     *
     * @return ArchiveAd
     */
    public function setAdMain(\Fa\Bundle\AdBundle\Entity\AdMain $adMain = null)
    {
        $this->ad_main = $adMain;

        return $this;
    }

    /**
     * Get ad main
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAdMain()
    {
        return $this->ad_main;
    }

    /**
     * Set user_id
     *
     * @param integer $user_id
     *
     * @return ArchiveAd
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set archived_at
     *
     * @param integer $archived_at
     *
     * @return ArchiveAd
     */
    public function setArchivedAt($archived_at)
    {
        $this->archived_at = $archived_at;

        return $this;
    }

    /**
     * Get archived_at
     *
     * @return integer
     */
    public function getArchivedAt()
    {
        return $this->archived_at;
    }

    /**
     * Set ad_data
     *
     * @param string $ad_data
     *
     * @return ArchiveAd
     */
    public function setAdData($ad_data)
    {
        $this->ad_data = $ad_data;

        return $this;
    }

    /**
     * Get ad_data
     *
     * @return string
     */
    public function getAdData()
    {
        return $this->ad_data;
    }

    /**
     * Set ad_vertical_data
     *
     * @param string $ad_vertical_data
     *
     * @return ArchiveAd
     */
    public function setAdVerticalData($ad_vertical_data)
    {
        $this->ad_vertical_data = $ad_vertical_data;

        return $this;
    }

    /**
     * Get ad_vertical_data
     *
     * @return string
     */
    public function getAdVerticalData()
    {
        return $this->ad_vertical_data;
    }

    /**
     * Set ad_location_data
     *
     * @param string $ad_location_data
     *
     * @return ArchiveAd
     */
    public function setAdLocationData($ad_location_data)
    {
        $this->ad_location_data = $ad_location_data;

        return $this;
    }

    /**
     * Get ad_location_data
     *
     * @return string
     */
    public function getAdLocationData()
    {
        return $this->ad_location_data;
    }

    /**
     * Set ad_moderate_data
     *
     * @param string $ad_moderate_data
     *
     * @return ArchiveAd
     */
    public function setAdModerateData($ad_moderate_data)
    {
        $this->ad_moderate_data = $ad_moderate_data;

        return $this;
    }

    /**
     * Get ad_moderate_data
     *
     * @return string
     */
    public function getAdModerateData()
    {
        return $this->ad_moderate_data;
    }

    /**
     * Set ad_view_counter
     *
     * @param string $ad_view_counter
     *
     * @return ArchiveAd
     */
    public function setAdViewCounter($ad_view_counter)
    {
        $this->ad_view_counter = $ad_view_counter;

        return $this;
    }

    /**
     * Get ad_view_counter
     *
     * @return string
     */
    public function getAdViewCounter()
    {
        return $this->ad_view_counter;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * Add Archive Ad location
     *
     * @param \Fa\Bundle\ArchiveBundle\Entity\ArchiveAdLocation $adLocations
     * @return ArchiveAd
     */
    public function addAdLocations(\Fa\Bundle\ArchiveBundle\Entity\ArchiveAdLocation $adLocations)
    {
        $this->ad_locations[] = $adLocations;
        
        return $this;
    }
    
    /**
     * Remove Archive Ad location
     *
     * @param \Fa\Bundle\ArchiveBundle\Entity\ArchiveAdLocation $adLocations
     */
    public function removeAdLocations(\Fa\Bundle\ArchiveBundle\Entity\ArchiveAdLocation $adLocations)
    {
        $this->ad_locations->removeElement($adLocations);
    }
    
    /**
     * Get Archive Ad location
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdLocations()
    {
        return $this->ad_locations;
    }
}
