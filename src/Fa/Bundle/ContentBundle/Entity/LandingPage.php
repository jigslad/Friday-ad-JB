<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * This table is used to store information about landing page.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="landing_page")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\LandingPageRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\ContentBundle\Entity\LandingPageTranslation")
 * @ORM\HasLifecycleCallbacks
 */
class LandingPage
{
    use \Fa\Bundle\CoreBundle\Manager\UploadFileManager;

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
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Translatable
     * @Assert\NotBlank(message="Please enter intro text.", groups={"new", "edit"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="h1_tag", type="string", length=500, nullable=true)
     */
    private $h1_tag;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_description", type="text", nullable=true)
     */
    private $meta_description;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_keywords", type="text", nullable=true)
     */
    private $meta_keywords;

    /**
     * @var string
     *
     * @ORM\Column(name="page_title", type="string", length=500, nullable=true)
     */
    private $page_title;

    /**
     * @var text
     *
     * @ORM\Column(name="criteria", type="text", nullable=true)
     */
    private $criteria;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", length=3)
     * @Assert\NotBlank(message="Please landing page type.", groups={"new", "edit"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=100, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=100, nullable=true)
     */
    private $file_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     * })
     * @Assert\NotBlank(message="Please select category.", groups={"new", "edit"})
     */
    private $category;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\ContentBundle\Entity\LandingPageTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /** @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="no_index", type="boolean", nullable=true, options={"default" = 0})
     */
    private $no_index;

    /**
     * @var boolean
     *
     * @ORM\Column(name="no_follow", type="boolean", nullable=true, options={"default" = 0})
     */
    private $no_follow;

    /**
     * @var string
     *
     * @ORM\Column(name="canonical_url", type="string", length=255, nullable=true)
     * @Assert\Url(message="Please enter valid canonical url.", groups={"new", "edit"})
     */
    private $canonical_url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="canonical_url_status", type="boolean", nullable=true, options={"default" = 0})
     */
    private $canonical_url_status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="popular_search", type="boolean", nullable=true, options={"default" = 0})
     */
    private $popular_search;

    /**
     * Validate canonical url.
     *
     * @param ExecutionContextInterface $context
     */
    public function validateCanonicalUrl(ExecutionContextInterface $context)
    {
        if ($this->canonical_url_status && !trim($this->canonical_url)) {
            $context->addViolationAt(
                'canonical_url',
                'Please enter canonical url.'
            );
        }
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
     * Validate canonical url.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
     *
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

    /**
     * Set path value.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setPathValue()
    {
        $this->path = $this->getUploadDir();
    }

    /**
    * Get upload file directory.
    *
    * @return string
    */
    protected function getUploadDir()
    {
        return 'uploads/landingpage';
    }

    /**
     * Get upload file root directory.
     *
     * @return string
     */
    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    /**
     * Get file field.
     *
     * @return string
     */
    public function getFileField()
    {
        return $this->getFileName();
    }

    /**
     * Set file field.
     *
     * @param unknown $file
     * @return Ambigous <\Fa\Bundle\ContentBundle\Entity\LandingPage, \Fa\Bundle\ContentBundle\Entity\LandingPage>
     */
    public function setFileField($file)
    {
        return $this->setFileName($file);
    }

    /**
     * Set criteria.
     *
     * @param string $criteria
     *
     * @return LandingPage
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Get criteria.
     *
     * @return string
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Set type.
     *
     * @param integer $type
     *
     * @return LandingPage
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return LandingPage
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set file name.
     *
     * @param string $fileName
     *
     * @return LandingPage
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Get file name.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return LandingPage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return LandingPage
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated at.
     *
     * @param integer $updatedAt
     *
     * @return LandingPage
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set description.
     *
     * @param string $description
     *
     * @return LandingPage
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set h1 tag.
     *
     * @param string $h1Tag
     *
     * @return LandingPage
     */
    public function setH1Tag($h1Tag)
    {
        $this->h1_tag = $h1Tag;

        return $this;
    }

    /**
     * Get h1 tag.
     *
     * @return string
     */
    public function getH1Tag()
    {
        return $this->h1_tag;
    }

    /**
     * Set meta description.
     *
     * @param string $metaDescription
     *
     * @return LandingPage
     */
    public function setMetaDescription($metaDescription)
    {
        $this->meta_description = $metaDescription;

        return $this;
    }

    /**
     * Get meta description.
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->meta_description;
    }

    /**
     * Set meta keywords.
     *
     * @param string $metaKeywords
     *
     * @return LandingPage
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->meta_keywords = $metaKeywords;

        return $this;
    }

    /**
     * Get meta keywords.
     *
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->meta_keywords;
    }

    /**
     * Set page title.
     *
     * @param string $pageTitle
     *
     * @return LandingPage
     */
    public function setPageTitle($pageTitle)
    {
        $this->page_title = $pageTitle;

        return $this;
    }

    /**
     * Get page title.
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->page_title;
    }

    /**
     * Set category.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     *
     * @return LandingPage
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Add translation.
     *
     * @param \Fa\Bundle\ContentBundle\Entity\LandingPageTranslation $translation
     *
     * @return LandingPage
     */
    public function addTranslation(\Fa\Bundle\ContentBundle\Entity\LandingPageTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation.
     *
     * @param \Fa\Bundle\ContentBundle\Entity\LandingPageTranslation $translation
     */
    public function removeTranslation(\Fa\Bundle\ContentBundle\Entity\LandingPageTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Set no index.
     *
     * @param boolean $no_index
     *
     * @return Object
     */
    public function setNoIndex($no_index)
    {
        $this->no_index = $no_index;

        return $no_index;
    }

    /**
     * Get no index.
     *
     * @return boolean
     */
    public function getNoIndex()
    {
        return $this->no_index;
    }

    /**
     * Set no follow.
     *
     * @param boolean $no_follow
     *
     * @return Object
     */
    public function setNoFollow($no_follow)
    {
        $this->no_follow = $no_follow;

        return $no_follow;
    }

    /**
     * Get no follow.
     *
     * @return boolean
     */
    public function getNoFollow()
    {
        return $this->no_follow;
    }

    /**
     * Set canonical url.
     *
     * @param boolean $canonical_url
     *
     * @return Object
     */
    public function setCanonicalUrl($canonical_url)
    {
        $this->canonical_url = $canonical_url;

        return $canonical_url;
    }

    /**
     * Get canonical url.
     *
     * @return boolean
     */
    public function getCanonicalUrl()
    {
        return $this->canonical_url;
    }

    /**
     * Set canonical url status.
     *
     * @param boolean $canonical_url_status
     *
     * @return Object
     */
    public function setCanonicalUrlStatus($canonical_url_status)
    {
        $this->canonical_url_status = $canonical_url_status;

        return $canonical_url_status;
    }

    /**
     * Get canonical url status.
     *
     * @return boolean
     */
    public function getCanonicalUrlStatus()
    {
        return $this->canonical_url_status;
    }

    /**
     * Set popular_search.
     *
     * @param string $popular_search
     * @return LandingPage
     */
    public function setPopularSearch($popular_search)
    {
        $this->popular_search = $popular_search;

        return $this;
    }

    /**
     * Get popular_search.
     *
     * @return string
     */
    public function getPopularSearch()
    {
        return $this->popular_search;
    }
}
