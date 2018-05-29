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
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="seo_tool", indexes={@ORM\Index(name="fa_seo_tool_h1_tag_index", columns={"h1_tag"}), @ORM\Index(name="fa_seo_tool_page_title_index", columns={"page_title"}), @ORM\Index(name="fa_seo_tool_target_url_index", columns={"target_url"}), @ORM\Index(name="fa_seo_tool_source_url_index", columns={"source_url"}), @ORM\Index(name="fa_seo_tool_canonical_url_index", columns={"canonical_url"}), @ORM\Index(name="fa_seo_tool_list_content_title_index", columns={"list_content_title"}), @ORM\Index(name="fa_seo_tool_popular_search_index", columns={"popular_search"}), @ORM\Index(name="fa_seo_tool_top_link_index", columns={"top_link"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\SeoToolRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\SeoToolListener" })
 * @ORM\HasLifecycleCallbacks
 */
class SeoTool
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
     * @ORM\Column(name="source_url", type="string", length=500, nullable=true)
     */
    private $source_url;

    /**
     * @var string
     *
     * @ORM\Column(name="target_url", type="string", length=500, nullable=true)
     */
    private $target_url;


    /**
     * @var string
     *
     * @ORM\Column(name="image_alt", type="string", length=500, nullable=true)
     */
    private $image_alt;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_2", type="string", length=500, nullable=true)
     */
    private $image_alt_2;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_3", type="string", length=500, nullable=true)
     */
    private $image_alt_3;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_4", type="string", length=500, nullable=true)
     */
    private $image_alt_4;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_5", type="string", length=500, nullable=true)
     */
    private $image_alt_5;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_6", type="string", length=500, nullable=true)
     */
    private $image_alt_6;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_7", type="string", length=500, nullable=true)
     */
    private $image_alt_7;

    /**
     * @var string
     *
     * @ORM\Column(name="image_alt_8", type="string", length=500, nullable=true)
     */
    private $image_alt_8;

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
     * @ORM\Column(name="page", type="string", length=10)
     */
    private $page;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Assert\NotBlank(message="Please select status.")
     */
    private $status;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\AdMain
     *
     * @ORM\OneToOne(targetEntity="Fa\Bundle\AdBundle\Entity\AdMain")
     * @ORM\JoinColumn(name="ad_main", referencedColumnName="id", onDelete="CASCADE")
     */
    private $ad_main;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $category;

    /**
     * @var boolean
     *
     * @ORM\Column(name="popular_search", type="boolean", nullable=true, options={"default" = 0})
     */
    private $popular_search;

    /**
     * @var boolean
     *
     * @ORM\Column(name="top_link", type="boolean", nullable=true, options={"default" = 0})
     */
    private $top_link;

    /**
     * @var string
     *
     * @ORM\Column(name="canonical_url", type="string", length=255, nullable=true)
     * @Assert\Url(message="Please enter valid canonical url.")
     */
    private $canonical_url;

    /**
     * @var string
     *
     * @ORM\Column(name="list_content_title", type="string", length=255, nullable=true)
     */
    private $list_content_title;

    /**
     * @var string
     *
     * @ORM\Column(name="list_content_detail", type="text", nullable=true)
     */
    private $list_content_detail;

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
     * Set source_url tag.
     *
     * @param string $source_url
     *
     * @return StaticPage
     */
    public function setSourceUrl($source_url)
    {
        $this->source_url = $source_url;

        return $this;
    }

    /**
     * Get source_url.
     *
     * @return string
     */
    public function getSourceUrl()
    {
        return $this->source_url;
    }

    /**
     * Set target_url tag.
     *
     * @param string $sourceUrl
     *
     * @return StaticPage
     */
    public function setTargetUrl($target_url)
    {
        $this->target_url = $target_url;

        return $this;
    }

    /**
     * Get target_url.
     *
     * @return string
     */
    public function getTargetUrl()
    {
        return $this->target_url;
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
     * Set page.
     *
     * @param boolean $page
     *
     * @return Object
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $page;
    }

    /**
     * Get page.
     *
     * @return string
     */
    public function getPage()
    {
        return $this->page;
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
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return Ad
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
     * Set image_alt.
     *
     * @param string $image_alt
     * @return SeoTool
     */
    public function setImageAlt($image_alt)
    {
        $this->image_alt = $image_alt;

        return $this;
    }

    /**
     * Get image_alt.
     *
     * @return string
     */
    public function getImageAlt()
    {
        return $this->image_alt;
    }

    /**
     * Set image_alt_2.
     *
     * @param string $image_alt_2
     * @return SeoTool
     */
    public function setImageAlt2($image_alt_2)
    {
        $this->image_alt_2 = $image_alt_2;

        return $this;
    }

    /**
     * Get image_alt_2.
     *
     * @return string
     */
    public function getImageAlt2()
    {
        return $this->image_alt_2;
    }

    /**
     * Set image_alt_3.
     *
     * @param string $image_alt_3
     * @return SeoTool
     */
    public function setImageAlt3($image_alt_3)
    {
        $this->image_alt_3 = $image_alt_3;

        return $this;
    }

    /**
     * Get image_alt_3.
     *
     * @return string
     */
    public function getImageAlt3()
    {
        return $this->image_alt_3;
    }

    /**
     * Set image_alt_4.
     *
     * @param string $image_alt_4
     * @return SeoTool
     */
    public function setImageAlt4($image_alt_4)
    {
        $this->image_alt_4 = $image_alt_4;

        return $this;
    }

    /**
     * Get image_alt_4.
     *
     * @return string
     */
    public function getImageAlt4()
    {
        return $this->image_alt_4;
    }

    /**
     * Set image_alt_5.
     *
     * @param string $image_alt_5
     * @return SeoTool
     */
    public function setImageAlt5($image_alt_5)
    {
        $this->image_alt_5 = $image_alt_5;

        return $this;
    }

    /**
     * Get image_alt_5.
     *
     * @return string
     */
    public function getImageAlt5()
    {
        return $this->image_alt_5;
    }

    /**
     * Set image_alt_6.
     *
     * @param string $image_alt_6
     * @return SeoTool
     */
    public function setImageAlt6($image_alt_6)
    {
        $this->image_alt_6 = $image_alt_6;

        return $this;
    }

    /**
     * Get image_alt_6.
     *
     * @return string
     */
    public function getImageAlt6()
    {
        return $this->image_alt_6;
    }

    /**
     * Set image_alt_7.
     *
     * @param string $image_alt_7
     * @return SeoTool
     */
    public function setImageAlt7($image_alt_7)
    {
        $this->image_alt_7 = $image_alt_7;

        return $this;
    }

    /**
     * Get image_alt_7.
     *
     * @return string
     */
    public function getImageAlt7()
    {
        return $this->image_alt_7;
    }

    /**
     * Set image_alt_8.
     *
     * @param string $image_alt_8
     * @return SeoTool
     */
    public function setImageAlt8($image_alt_8)
    {
        $this->image_alt_8 = $image_alt_8;

        return $this;
    }

    /**
     * Get image_alt_8.
     *
     * @return string
     */
    public function getImageAlt8()
    {
        return $this->image_alt_8;
    }

    /**
     * Set popular_search.
     *
     * @param string $popular_search
     * @return SeoTool
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

    /**
     * Set top_link.
     *
     * @param string $top_link
     * @return SeoTool
     */
    public function setTopLink($top_link)
    {
        $this->top_link = $top_link;

        return $this;
    }

    /**
     * Get top_link.
     *
     * @return string
     */
    public function getTopLink()
    {
        return $this->top_link;
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

    /**
     * Get list content title.
     *
     * @return string
     */
    public function getListContentTitle()
    {
        return $this->list_content_title;
    }

    /**
     * Set canonical url.
     *
     * @param string $canonical_url
     *
     * @return SeoTool
     */
    public function setListContentTitle($list_content_title)
    {
        $this->list_content_title = $list_content_title;

        return $list_content_title;
    }

    /**
     * Get list content detail.
     *
     * @return string
     */
    public function getListContentDetail()
    {
        return $this->list_content_detail;
    }

    /**
     * Set canonical url.
     *
     * @param string $canonical_url
     *
     * @return SeoTool
     */
    public function setListContentDetail($list_content_detail)
    {
        $this->list_content_detail = $list_content_detail;

        return $list_content_detail;
    }
}
