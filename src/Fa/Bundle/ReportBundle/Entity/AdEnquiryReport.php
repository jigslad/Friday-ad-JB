<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to ad report.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_enquiry_report", indexes={@ORM\Index(name="fa_report_ad_enquiry_report_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fa_report_ad_enquiry_report_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_ad_enquiry_report_category_id_index", columns={"category_id"}), @ORM\Index(name="fa_report_ad_enquiry_report_county_id_index", columns={"county_id"}), @ORM\Index(name="fa_report_ad_enquiry_report_town_id_index", columns={"town_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\AdEnquiryReportRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdEnquiryReport
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
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer")
     */
    private $ad_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer")
     */
    private $category_id;

    /**
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=20, nullable=true)
     */
    private $postcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="county_id", type="integer", nullable=true)
     */
    private $county_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="town_id", type="integer", nullable=true)
     */
    private $town_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="saved_ads", type="integer", nullable=true, options={"default" = 0})
     */
    private $saved_ads;

    /**
     * @var integer
     *
     * @ORM\Column(name="title_word_count", type="integer", nullable=true, options={"default" = 0})
     */
    private $title_word_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="title_character_count", type="integer", nullable=true, options={"default" = 0})
     */
    private $title_character_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="description_word_count", type="integer", nullable=true, options={"default" = 0})
     */
    private $description_word_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="description_character_count", type="integer", nullable=true, options={"default" = 0})
     */
    private $description_character_count;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_privacy_number", type="boolean", nullable=true)
     */
    private $use_privacy_number;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10, nullable=true)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

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
     * Set ad_id
     *
     * @param integer $adId
     * @return AdReportEnquiry
     */
    public function setAdId($adId)
    {
        $this->ad_id = $adId;

        return $this;
    }

    /**
     * Get ad_id
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return AdReportEnquiry
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return AdReportEnquiry
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set category_id
     *
     * @param integer $categoryId
     * @return AdReportEnquiry
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get category_id
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set postcode
     *
     * @param string $postcode
     * @return AdReportEnquiry
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set county_id
     *
     * @param integer $countyId
     * @return AdReportEnquiry
     */
    public function setCountyId($countyId)
    {
        $this->county_id = $countyId;

        return $this;
    }

    /**
     * Get county_id
     *
     * @return integer
     */
    public function getCountyId()
    {
        return $this->county_id;
    }

    /**
     * Set town_id
     *
     * @param integer $townId
     * @return AdReportEnquiry
     */
    public function setTownId($townId)
    {
        $this->town_id = $townId;

        return $this;
    }

    /**
     * Get town_id
     *
     * @return integer
     */
    public function getTownId()
    {
        return $this->town_id;
    }

    /**
     * Set saved_ads
     *
     * @param integer $savedAds
     * @return AdReportEnquiry
     */
    public function setSavedAds($savedAds)
    {
        $this->saved_ads = $savedAds;

        return $this;
    }

    /**
     * Get saved_ads
     *
     * @return integer
     */
    public function getSavedAds()
    {
        return $this->saved_ads;
    }

    /**
     * Set title_word_count
     *
     * @param integer $titleWordCount
     * @return AdReportEnquiry
     */
    public function setTitleWordCount($titleWordCount)
    {
        $this->title_word_count = $titleWordCount;

        return $this;
    }

    /**
     * Get title_word_count
     *
     * @return integer
     */
    public function getTitleWordCount()
    {
        return $this->title_word_count;
    }

    /**
     * Set title_character_count
     *
     * @param integer $titleCharacterCount
     * @return AdReportEnquiry
     */
    public function setTitleCharacterCount($titleCharacterCount)
    {
        $this->title_character_count = $titleCharacterCount;

        return $this;
    }

    /**
     * Get title_character_count
     *
     * @return integer
     */
    public function getTitleCharacterCount()
    {
        return $this->title_character_count;
    }

    /**
     * Set description_word_count
     *
     * @param integer $descriptionWordCount
     * @return AdReportEnquiry
     */
    public function setDescriptionWordCount($descriptionWordCount)
    {
        $this->description_word_count = $descriptionWordCount;

        return $this;
    }

    /**
     * Get description_word_count
     *
     * @return integer
     */
    public function getDescriptionWordCount()
    {
        return $this->description_word_count;
    }

    /**
     * Set description_character_count
     *
     * @param integer $descriptionCharacterCount
     * @return AdReportEnquiry
     */
    public function setDescriptionCharacterCount($descriptionCharacterCount)
    {
        $this->description_character_count = $descriptionCharacterCount;

        return $this;
    }

    /**
     * Get description_character_count
     *
     * @return integer
     */
    public function getDescriptionCharacterCount()
    {
        return $this->description_character_count;
    }

    /**
     * Set use_privacy_number
     *
     * @param boolean $usePrivacyNumber
     * @return AdReportEnquiry
     */
    public function setUsePrivacyNumber($usePrivacyNumber)
    {
        $this->use_privacy_number = $usePrivacyNumber;

        return $this;
    }

    /**
     * Get use_privacy_number
     *
     * @return boolean
     */
    public function getUsePrivacyNumber()
    {
        return $this->use_privacy_number;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return AdReportEnquiry
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return AdReportEnquiry
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
