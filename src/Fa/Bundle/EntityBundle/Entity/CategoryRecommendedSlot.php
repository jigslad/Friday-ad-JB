<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This table is used to store information about seo tool popular keywords.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="category_recommended_slot")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\CategoryRecommendedSlotRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\CategoryRecommendedSlotListener" })
 * @ORM\HasLifecycleCallbacks
 */
class CategoryRecommendedSlot
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
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_title", type="string", length=500, nullable=false)
     */
    private $sub_title;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /** @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", length=10, nullable=true)
     */
    private $user_id;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * })
     */
    private $category;

    /**
    * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
    */
    private $slot_file;

    /**
     * @var string
     *
     * @ORM\Column(name="slot_filename", type="string", length=250, nullable=true)
     */
    private $slot_filename;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_searchlist", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_searchlist = false;

    /**
     * @var string
     *
     * @ORM\Column(name="creative_group", type="string", length=250, nullable=true)
     */
    private $creative_group;

    /**
     * @var string
     *
     * @ORM\Column(name="creative_ord", type="integer", length=2, nullable=true)
     */
    private $creative_ord;
        
    /**
     * @var string
     *
     * @ORM\Column(name="display_url", type="string", length=255, nullable=false)
     */
    private $display_url;
    
    /**
     * @var string
     *
     * @ORM\Column(name="cta_text", type="string", length=255, nullable=false)
     */
    private $cta_text;
    
    /**
     * @var string
     *
     * @ORM\Column(name="mobile_title", type="string", length=255, nullable=false)
     */
    private $mobile_title;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="show_sponsored_lbl", type="boolean", nullable=true, options={"default" = 0})
     */
    private $show_sponsored_lbl = false;

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
     * Set created at value.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
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
     * @return \Fa\Bundle\EntityBundle\Entity\Category $category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set title.
     *
     * @param string $title
     * @return CategoryRecommendedSlot
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
     * Set url.
     *
     * @param string $url
     * @return CategoryRecommendedSlot
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set sub_title.
     *
     * @param string $sub_title
     * @return CategoryRecommendedSlot
     */
    public function setSubTitle($sub_title)
    {
        $this->sub_title = $sub_title;

        return $this;
    }

    /**
     * Get sub_title.
     *
     * @return string
     */
    public function getSubTitle()
    {
        return $this->sub_title;
    }

    /**
     * Set user_id.
     *
     * @param integer $user_id
     * @return CategoryRecommendedSlot
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set slot_file.
     *
     * @param UploadedFile $slot_file
     * @return CategoryRecommendedSlot
     */
    public function setSlotFile(UploadedFile $slot_file = null)
    {
        $this->slot_file = $slot_file;

        return $this;
    }

    /**
     * Get slot_file.
     *
     * @return UploadedFile
     */
    public function getSlotFile()
    {
        return $this->slot_file;
    }

    /**
    * Get upload file directory.
    *
    * @return string
    */
    public function getUploadDir()
    {
        return 'uploads/category_recommended_slots';
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
        return null === $this->getSlotFileName() ? null : $this->getUploadDir().'/'.$this->getSlotFileName();
    }

    /**
     * Get upload file web absolute path.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return null === $this->getSlotFileName() ? null : $this->getUploadRootDir().'/'.$this->getSlotFileName();
    }

    /**
     * Set slot_filename
     *
     * @param string $slot_filename
     * @return CategoryRecommendedSlot
     */
    public function setSlotFilename($slot_filename)
    {
        $this->slot_filename = $slot_filename;

        return $this;
    }

    /**
     * Get slot_filename
     *
     * @return string
     */
    public function getSlotFilename()
    {
        return $this->slot_filename;
    }

    /**
     * Set is_searchlist.
     *
     * @param boolean $is_searchlist
     * @return CategoryRecommendedSlot
     */

    public function setIsSearchlist($is_searchlist)
    {
        $this->is_searchlist = $is_searchlist;

        return $this;
    }

    /**
     * Get is_searchlist.
     *
     * @return boolean
     */
    public function getIsSearchlist()
    {
        return $this->is_searchlist;
    }

    /**
     * Set creative_group.
     *
     * @param string $creative_group
     * @return CategoryRecommendedSlot
     */
    public function setCreativeGroup($creative_group)
    {
        $this->creative_group = $creative_group;

        return $this;
    }

    /**
     * Get creative_group.
     *
     * @return string
     */
    public function getCreativeGroup()
    {
        return $this->creative_group;
    }

    /**
     * Set creative_ord.
     *
     * @param integer $creative_ord
     * @return CategoryRecommendedSlot
     */
    public function setCreativeOrd($creative_ord)
    {
        $this->creative_ord = $creative_ord;

        return $this;
    }

    /**
     * Get creative_ord.
     *
     * @return integer
     */
    public function getCreativeOrd()
    {
        return $this->creative_ord;
    }
    
    /**
     * Set display_url.
     *
     * @param string $display_url
     * @return CategoryRecommendedSlot
     */
    public function setDisplayUrl($display_url)
    {
        $this->display_url = $display_url;
        
        return $this;
    }
    
    /**
     * Get display_url.
     *
     * @return string
     */
    public function getDisplayUrl()
    {
        return $this->display_url;
    }
    
    /**
     * Set cta_text.
     *
     * @param string $cta_text
     * @return CategoryRecommendedSlot
     */
    public function setCtaText($cta_text)
    {
        $this->cta_text = $cta_text;
        
        return $this;
    }
    
    /**
     * Get cta_text.
     *
     * @return string
     */
    public function getCtaText()
    {
        return $this->cta_text;
    }
    
    /**
     * Set mobile_title.
     *
     * @param string $mobile_title
     * @return CategoryRecommendedSlot
     */
    public function setMobileTitle($mobile_title)
    {
        $this->mobile_title = $mobile_title;
        
        return $this;
    }
    
    /**
     * Get mobile_title.
     *
     * @return string
     */
    public function getMobileTitle()
    {
        return $this->mobile_title;
    }
    
    /**
     * Set show_sponsored_lbl
     *
     * @param boolean $show_sponsored_lbl
     * @return CategoryRecommendedSlot
     */
    
    public function setShowSponsoredLbl($show_sponsored_lbl)
    {
        $this->show_sponsored_lbl = $show_sponsored_lbl;
        
        return $this;
    }
    
    /**
     * Get show_sponsored_lbl.
     *
     * @return boolean
     */
    public function getShowSponsoredLbl()
    {
        return $this->show_sponsored_lbl;
    }
}
