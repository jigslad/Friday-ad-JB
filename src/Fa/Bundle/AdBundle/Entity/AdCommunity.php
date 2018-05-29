<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdCommunity
 *
 * This table is used to store ad other information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_community")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdCommunityRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdCommunity
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
     * @ORM\Column(name="experience_level_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $experience_level_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="education_level_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $education_level_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="cuisine_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $cuisine_type_id;

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
     * Set experience_level_id
     *
     * @param integer $experience_level_id
     * @return AdCommunity
     */
    public function setExperienceLevelId($experience_level_id)
    {
        $this->experience_level_id = $experience_level_id;

        return $this;
    }

    /**
     * Get experience_level_id
     *
     * @return integer
     */
    public function getExperienceLevelId()
    {
        return $this->experience_level_id ;
    }

    /**
     * Set education_level_id
     *
     * @param integer $education_level_id
     * @return AdCommunity
     */
    public function setEducationLevelId($education_level_id)
    {
        $this->education_level_id = $education_level_id;

        return $this;
    }

    /**
     * Get education_level_id
     *
     * @return integer
     */
    public function getEducationLevelId()
    {
        return $this->education_level_id;
    }

    /**
     * Set meta_data
     *
     * @param integer $metaData
     * @return AdForSale
     */
    public function setMetaData($metaData)
    {
        $this->meta_data = $metaData;

        return $this;
    }

    /**
     * Get meta_data
     *
     * @return integer
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    /**
     * Set cuisine_type_id.
     *
     * @param string $cuisine_type_id
     * @return AdCommunity
     */
    public function setCuisineTypeId($cuisine_type_id)
    {
        $this->cuisine_type_id = $cuisine_type_id;

        return $this;
    }

    /**
     * Get cuisine_type_id.
     *
     * @return string
     */
    public function getCuisineTypeId()
    {
        return $this->cuisine_type_id;
    }
}
