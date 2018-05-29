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

/**
 * This table is used to store information about static page.
 *
 * @author Amit Limbadia <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="seo_tool_override", indexes={@ORM\Index(name="fa_seo_tool_override_h1_tag_index", columns={"h1_tag"}), @ORM\Index(name="fa_seo_tool_override_page_title_index", columns={"page_title"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\SeoToolOverrideRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\SeoToolOverrideListener" })
 * @ORM\HasLifecycleCallbacks
 */
class SeoToolOverride
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
     * @ORM\Column(name="h1_tag", type="string", length=500, nullable=true)
     */
    private $h1_tag;

    /**
     * @var string
     *
     * @ORM\Column(name="page_title", type="string", length=500, nullable=true)
     */
    private $page_title;

    /**
     * @var string
     *
     * @ORM\Column(name="page_url", type="string", length=500, nullable=true)
     */
    private $page_url;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_description", type="text", nullable=true)
     */
    private $meta_description;

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
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Assert\NotBlank(message="Please select status.")
     */
    private $status;

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
     */
    private $canonical_url;

    /**
     * Constructor.
     */
    public function __construct()
    {
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
     * Set h1 tag.
     *
     * @param string $h1Tag
     *
     * @return SeoToolOverride
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
     * Set page title.
     *
     * @param string $pageTitle
     *
     * @return SeoToolOverride
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
     * Set page url.
     *
     * @param string $pageUrl
     *
     * @return SeoToolOverride
     */
    public function setPageUrl($pageUrl)
    {
     $this->page_url = $pageUrl;

     return $this;
    }

    /**
     * Get page url.
     *
     * @return string
     */
    public function getPageUrl()
    {
     return $this->page_url;
    }

    /**
     * Set meta description.
     *
     * @param string $metaDescription
     *
     * @return SeoToolOverride
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
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return SeoToolOverride
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
     * @return SeoToolOverride
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
     * Set status.
     *
     * @param boolean $status
     *
     * @return SeoToolOverride
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
     * Set no index.
     *
     * @param boolean $no_index
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
     * @param string $canonical_url
     *
     * @return SeoTool
     */
    public function setCanonicalUrl($canonical_url)
    {
        $this->canonical_url = $canonical_url;

        return $canonical_url;
    }

    /**
     * Get canonical url.
     *
     * @return string
     */
    public function getCanonicalUrl()
    {
        return $this->canonical_url;
    }
}