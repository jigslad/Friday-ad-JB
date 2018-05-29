<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdForSale
 *
 * This table is used to store ad other information.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_for_sale")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdForSaleRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 *
 */
class AdForSale
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
     * @ORM\Column(name="age_range_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $age_range_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="brand_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $brand_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="brand_clothing_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $brand_clothing_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="business_type_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $business_type_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="condition_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $condition_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="colour_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $colour_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="main_colour_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $main_colour_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="size_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $size_id;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_data", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $meta_data;

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
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return AdForSale
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
     * Set age_range_id
     *
     * @param integer $ageRangeId
     * @return AdForSale
     */
    public function setAgeRangeId($ageRangeId)
    {
        $this->age_range_id = $ageRangeId;

        return $this;
    }

    /**
     * Get age_range_id
     *
     * @return integer
     */
    public function getAgeRangeId()
    {
        return $this->age_range_id;
    }

    /**
     * Set brand_id
     *
     * @param integer $brandId
     * @return AdForSale
     */
    public function setBrandId($brandId)
    {
        $this->brand_id = $brandId;

        return $this;
    }

    /**
     * Get brand_id
     *
     * @return integer
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * Set brand_clothing_id
     *
     * @param integer $brandClothingId
     * @return AdForSale
     */
    public function setBrandClothingId($brandClothingId)
    {
        $this->brand_clothing_id = $brandClothingId;

        return $this;
    }

    /**
     * Get brand_clothing_id
     *
     * @return integer
     */
    public function getBrandClothingId()
    {
        return $this->brand_clothing_id;
    }

    /**
     * Set business_type_id
     *
     * @param integer $businessTypeId
     * @return AdForSale
     */
    public function setBusinessTypeId($businessTypeId)
    {
        $this->business_type_id = $businessTypeId;

        return $this;
    }

    /**
     * Get business_type_id
     *
     * @return integer
     */
    public function getBusinessTypeId()
    {
        return $this->business_type_id;
    }

    /**
     * Set condition_id
     *
     * @param integer $conditionId
     * @return AdForSale
     */
    public function setConditionId($conditionId)
    {
        $this->condition_id = $conditionId;

        return $this;
    }

    /**
     * Get condition_id
     *
     * @return integer
     */
    public function getConditionId()
    {
        return $this->condition_id;
    }

    /**
     * Set colour_id
     *
     * @param integer $colourId
     * @return AdForSale
     */
    public function setColourId($colourId)
    {
        $this->colour_id = $colourId;

        return $this;
    }

    /**
     * Get colour_id
     *
     * @return integer
     */
    public function getColourId()
    {
        return $this->colour_id;
    }

    /**
     * Set main_colour_id
     *
     * @param integer $mainColourId
     * @return AdForSale
     */
    public function setMainColourId($mainColourId)
    {
        $this->main_colour_id = $mainColourId;

        return $this;
    }

    /**
     * Get main_colour_id
     *
     * @return integer
     */
    public function getMainColourId()
    {
        return $this->main_colour_id;
    }

    /**
     * Set size_id
     *
     * @param integer $sizeId
     * @return AdForSale
     */
    public function setSizeId($sizeId)
    {
        $this->size_id = $sizeId;

        return $this;
    }

    /**
     * Get size_id
     *
     * @return integer
     */
    public function getSizeId()
    {
        return $this->size_id;
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
}
