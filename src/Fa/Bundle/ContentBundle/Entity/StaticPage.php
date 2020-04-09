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
 * This table is used to store information about static page.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="static_page")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\StaticPageRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\ContentBundle\Entity\StaticPageTranslation")
 * @UniqueEntity(fields={"title"}, message="This title already exist in our database.")
 * @UniqueEntity(fields={"slug"}, message="This slug already exist in our database.")
 * @UniqueEntity(fields={"canonical_url"}, message="This url already exist in our database.")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\StaticPageListener" })
 * @ORM\HasLifecycleCallbacks
 */
class StaticPage
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
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=true)
     * @Gedmo\Translatable
     * @Assert\NotBlank(message="Please enter title.", groups={"static_block", "default"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Translatable
     * @Assert\NotBlank(message="Please enter description.", groups={"static_block", "default"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="additional_info", type="text", nullable=true)
     * @Gedmo\Translatable
     */
    private $additional_info;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", options={"default" = 1})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="h1_tag", type="string", length=150, nullable=true)
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
     * @ORM\Column(name="slug", type="string", length=150, nullable=true)
     * @Gedmo\Slug(fields={"title"}, updatable=false)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="page_title", type="string", length=150, nullable=true)
     */
    private $page_title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Assert\NotBlank(message="Please select status.", groups={"static_block", "default"})
     */
    private $status;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\ContentBundle\Entity\StaticPageTranslation", mappedBy="object", cascade={"persist","remove"})
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
     * @Assert\Url(message="Please enter valid canonical url.", groups={"default"})
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
     * @ORM\Column(name="include_in_footer", type="boolean", nullable=true, options={"default" = 0})
     */
    private $include_in_footer;

    /**
     * @var boolean
     *
     * @ORM\Column(name="include_in_mobile_footer", type="boolean", nullable=true, options={"default" = 0})
     */
    private $include_in_mobile_footer;

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
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return StaticPage
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set title.
     *
     * @param string $title
     *
     * @return StaticPage
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return StaticPage
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return StaticPage
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
     * Set additional info.
     *
     * @param string $additionalInfo
     *
     * @return StaticPage
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additional_info = $additionalInfo;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getAdditionalInfo()
    {
        return $this->additional_info;
    }

    /**
     * Set h1 tag.
     *
     * @param string $h1Tag
     *
     * @return StaticPage
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
     * @return StaticPage
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
     * @return StaticPage
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
     * Set slug.
     *
     * @param string $slug
     *
     * @return StaticPage
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set page title.
     *
     * @param string $pageTitle
     *
     * @return StaticPage
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
     * Set status.
     *
     * @param boolean $status
     *
     * @return StaticPage
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
     * @return StaticPage
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
     * @return StaticPage
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
     * Add translation.
     *
     * @param \Fa\Bundle\ContentBundle\Entity\StaticPageTranslation $translation
     *
     * @return StaticPage
     */
    public function addTranslation(\Fa\Bundle\ContentBundle\Entity\StaticPageTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation.
     *
     * @param \Fa\Bundle\ContentBundle\Entity\StaticPageTranslation $translation
     */
    public function removeTranslation(\Fa\Bundle\ContentBundle\Entity\StaticPageTranslation $translation)
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
     * Set created at value.
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
     * Set include in footer.
     *
     * @param boolean $include_in_footer
     *
     * @return Object
     */
    public function setIncludeInFooter($include_in_footer)
    {
        $this->include_in_footer = $include_in_footer;

        return $include_in_footer;
    }

    /**
     * Get include in footer.
     *
     * @return boolean
     */
    public function getIncludeInFooter()
    {
        return $this->include_in_footer;
    }

    /**
     * Set include in mobile footer.
     *
     * @param boolean $include_in_mobile_footer
     *
     * @return Object
     */
    public function setIncludeInMobileFooter($include_in_mobile_footer)
    {
        $this->include_in_mobile_footer = $include_in_mobile_footer;

        return $include_in_mobile_footer;
    }

    /**
     * Get include in mobile footer.
     *
     * @return boolean
     */
    public function getIncludeInMobileFooter()
    {
        return $this->include_in_mobile_footer;
    }

    /**
     * Set type.
     *
     * @param integer $type
     *
     * @return Upsell
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
}
