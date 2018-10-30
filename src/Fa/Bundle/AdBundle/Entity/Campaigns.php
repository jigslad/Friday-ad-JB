<?php
namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * campaigns
 *
 * @ORM\Table(name="campaigns")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\CampaignsRepository")
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 * @UniqueEntity(fields="slug", message="Campaign slug already exist in our system.")
 */
class Campaigns
{

    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     *
     * @var string @ORM\Column(name="campaign_name", type="string", length=255, nullable=true)
     */
    private $campaignName;

    /**
     *
     * @var string @ORM\Column(name="page_title", type="string", length=255, nullable=true)
     */
    private $pageTitle;

    /**
     *
     * @var string @ORM\Column(name="page_title_color", type="string", length=255, nullable=true)
     */
    private $pageTitleColor;

    /**
     *
     * @var string @ORM\Column(name="seo_page_title", type="string",length=255,  nullable=true)
     */
    private $seoPageTitle;

    /**
     *
     * @var string @ORM\Column(name="seo_page_description", type="text", nullable=true)
     */
    private $seoPageDescription;

    /**
     *
     * @var string @ORM\Column(name="seo_page_keywords", type="string",length=255,  nullable=true)
     */
    private $seoPageKeywords;

    /**
     *
     * @var string @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     *
     * @var string @ORM\Column(name="form_fill_times", type="integer", nullable=true)
     */
    private $formFillTimes;

    /**
     *
     * @var string @ORM\Column(name="discountCode", type="integer", nullable=true)
     */
    private $discountCode;

    /**
     *
     * @var string @ORM\Column(name="intro_text", type="text", nullable=true)
     */
    private $introText;

    /**
    * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
    */
    private $background_file;

    /**
     * @var string
     *
     * @ORM\Column(name="campaign_background_filename", type="string", length=100, nullable=true)
     */
    private $campaignBackgroundFileName;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     * @Gedmo\Versioned
     */
    private $category;

    /**
     *
     * @var integer @ORM\Column(name="campaign_status", type="smallint")
     */
    private $campaignStatus;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_not_deletable", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_not_deletable;

    /**
      * Set id
      *
      * @param string $id
      * @return Campaigns
      */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set is_not_deletable.
     *
     * @param string $is_not_deletable
     * @return Campaigns
    */
    public function setIsNotDeletable($is_not_deletable)
    {
        $this->is_not_deletable = $is_not_deletable;

        return $this;
    }

    /**
     * Get is_not_deletable.
     *
     * @return string
     */
    public function getIsNotDeletable()
    {
        return $this->is_not_deletable;
    }

    /**
     * Set campaignName
     *
     * @param string $campaignName
     * @return Campaigns
     */
    public function setCampaignName($campaignName)
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    /**
     * Get campaignName
     *
     * @return string
     */
    public function getCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * Set pageTitle
     *
     * @param string $pageTitle
     * @return Campaigns
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    /**
     * Get pageTitle
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
    * Set pageTitleColor
    *
    * @param string $pageTitleColor
    * @return Campaigns
    */
    public function setPageTitleColor($pageTitleColor)
    {
        $this->pageTitleColor = $pageTitleColor;

        return $this;
    }

    /**
     * Get pageTitleColor
     *
     * @return string
     */
    public function getPageTitleColor()
    {
        return $this->pageTitleColor;
    }

    /**
     * Set seoPageTitle
     *
     * @param string $seoPageTitle
     * @return Campaigns
     */
    public function setSeoPageTitle($seoPageTitle)
    {
        $this->seoPageTitle = $seoPageTitle;

        return $this;
    }

    /**
     * Get seoPageTitle
     *
     * @return string
     */
    public function getSeoPageTitle()
    {
        return $this->seoPageTitle;
    }

    /**
     * Set seoPageDescription
     *
     * @param string $seoPageDescription
     * @return Campaigns
     */
    public function setSeoPageDescription($seoPageDescription)
    {
        $this->seoPageDescription = $seoPageDescription;

        return $this;
    }

    /**
     * Get seoPageDescription
     *
     * @return string
     */
    public function getSeoPageDescription()
    {
        return $this->seoPageDescription;
    }

    /**
     * Set seoPageKeywords
     *
     * @param string $seoPageKeywords
     * @return Campaigns
     */
    public function setSeoPageKeywords($seoPageKeywords)
    {
        $this->seoPageKeywords = $seoPageKeywords;

        return $this;
    }

    /**
     * Get seoPageKeywords
     *
     * @return string
     */
    public function getSeoPageKeywords()
    {
        return $this->seoPageKeywords;
    }

    /**
    * Set slug
    *
    * @param string $slug
    * @return Campaigns
    */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
    * Set formFillTimes
    *
    * @param integer $formFillTimes
    * @return Campaigns
    */
    public function setFormFillTimes($formFillTimes)
    {
        $this->formFillTimes = $formFillTimes;

        return $this;
    }

    /**
     * Get formFillTimes
     *
     * @return integer
     */
    public function getFormFillTimes()
    {
        return $this->formFillTimes;
    }

    /**
     * Set discountCode
     *
     * @param integer $discountCode
     * @return Campaigns
     */
    public function setDiscountCode($discountCode)
    {
        $this->discountCode = $discountCode;

        return $this;
    }

    /**
     * Get discountCode
     *
     * @return integer
     */
    public function getDiscountCode()
    {
        return $this->discountCode;
    }

    /**
     * Set introText
     *
     * @param string $introText
     * @return Campaigns
     */
    public function setIntroText($introText)
    {
        $this->introText = $introText;

        return $this;
    }

    /**
     * Get introText
     *
     * @return string
     */
    public function getIntroText()
    {
        return $this->introText;
    }
    
    /**
     * Set campaignBackgroundFileName
     *
     * @param string $campaignBackgroundFileName
     * @return Campaigns
     */
    public function setCampaignBackgroundFileName($campaignBackgroundFileName)
    {
        $this->campaignBackgroundFileName = $campaignBackgroundFileName;

        return $this;
    }

    /**
     * Get campaignBackgroundFileName
     *
     * @return string
     */
    public function getCampaignBackgroundFileName()
    {
        return $this->campaignBackgroundFileName;
    }

    /**
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return Campaigns
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }


    /**
     * Set campaignStatus
     *
     * @param integer $campaignStatus
     * @return Campaigns
     */
    public function setCampaignStatus($campaignStatus)
    {
        $this->campaignStatus = $campaignStatus;

        return $this;
    }

    /**
     * Get campaignStatus
     *
     * @return integer
     */
    public function getCampaignStatus()
    {
        return $this->campaignStatus;
    }

    /**
     * Set background_file.
     *
     * @param UploadedFile $background_file
     * @return Campaigns
     */
    public function setBackgroundFile(UploadedFile $background_file = null)
    {
        $this->background_file = $background_file;

        return $this;
    }

    /**
     * Get background_file.
     *
     * @return UploadedFile
     */
    public function getBackgroundFile()
    {
        return $this->background_file;
    }

    /**
    * Get upload file directory.
    *
    * @return string
    */
    public function getUploadDir()
    {
        return 'uploads/campaigns';
    }

    /**
     * Get upload file root directory.
     *
     * @return string
     */
    public function getUploadRootDir()
    {
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    /**
     * Get upload file web path.
     *
     * @return string
     */
    public function getWebPath()
    {
        return null === $this->getCampaignBackgroundFileName() ? null : $this->getUploadDir().'/'.$this->getCampaignBackgroundFileName();
    }

    /**
     * Get upload file web absolute path.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return null === $this->getCampaignBackgroundFileName() ? null : $this->getUploadRootDir().'/'.$this->getCampaignBackgroundFileName();
    }
}
