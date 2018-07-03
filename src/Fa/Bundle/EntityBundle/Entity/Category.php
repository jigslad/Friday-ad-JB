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
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store category information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="category", indexes={@ORM\Index(name="fa_entity_category_name_index", columns={"name"}), @ORM\Index(name="fa_entity_category_slug_index", columns={"slug"}), @ORM\Index(name="fa_entity_category_full_slug_index", columns={"full_slug"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\CategoryRepository")
 * @Gedmo\Tree(type="nested")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\EntityBundle\Entity\CategoryTranslation")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\CategoryListener" })
 * @UniqueEntity(fields={"parent", "name"}, errorPath="name", message="This category name already exist in our database.")
 */
class Category
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
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Category name is required.")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="clean_name", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     */
    private $clean_name;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255)
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"name"}, unique=false, updatable=false)
     * @assert\NotBlank(message="Category slug is required.")
     * @Assert\Regex(pattern="/^[a-z0-9- ]+$/i", message="Please enter alpha numeric value only.")
     */
    private $slug;

    /**
     * @var integer
     *
     * @ORM\Column(name="lft", type="integer")
     * @Gedmo\TreeLeft
     */
    private $lft;

    /**
     * @var integer
     *
     * @ORM\Column(name="rgt", type="integer")
     * @Gedmo\TreeRight
     */
    private $rgt;

    /**
     * @var integer
     *
     * @ORM\Column(name="root", type="integer")
     * @Gedmo\TreeRoot
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="lvl", type="integer")
     * @Gedmo\TreeLevel
     */
    private $lvl;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\Category", mappedBy="parent")
     * @ORM\OrderBy({
     *     "lft"="ASC"
     * })
     */
    private $children;

    /**
     * @var string
     *
     * @ORM\Column(name="full_slug", type="string", length=500, nullable=true)
     */
    private $full_slug;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\CategoryTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\TreeParent
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity", mappedBy="categories")
     */
    private $entities;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Assert\NotBlank(message="Please select status.")
     */
    private $status;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Fa\Bundle\PromotionBundle\Entity\Upsell")
     * @ORM\JoinTable(name="category_upsell",
     *   joinColumns={
     *     @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="upsell_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     */
    private $upsells;

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
     * @ORM\Column(name="page_title", type="string", length=150, nullable=true)
     */
    private $page_title;

    /**
     * @var string
     *
     * @ORM\Column(name="page_description", type="text", nullable=true)
     */
    private $page_description;

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
     * @var boolean
     *
     * @ORM\Column(name="display_on_footer", type="boolean", nullable=true, options={"default" = 0})
     */
    private $display_on_footer;

    /**
     * @var string
     *
     * @ORM\Column(name="synonyms_keywords", type="text", nullable=true)
     */
    private $synonyms_keywords;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_nimber_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_nimber_enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="nimber_size", type="string", length=15, nullable=true)
     */
    private $nimber_size;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paa_disabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_paa_disabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_finance_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_finance_enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="finance_title", type="string", length=150, nullable=true)
     */
    private $finance_title;

    /**
     * @var string
     *
     * @ORM\Column(name="finance_url", type="text", nullable=true)
     */
    private $finance_url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_oneclickenq_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_oneclickenq_enabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_recommended_slot", type="boolean", nullable=true, options={"default" = 0})
     */
    private $has_recommended_slot = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_featured_upgrade_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_featured_upgrade_enabled;
    
    /**
     * @var string
     *
     * @ORM\Column(name="featured_upgrade_info", type="string", length=255, nullable=true)
     */
    private $featured_upgrade_info;
    
    /**
     * @var string
     *
     * @ORM\Column(name="featured_upgrade_btn_txt", type="text", length=25, nullable=true)
     */
    private $featured_upgrade_btn_txt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_recommended_slot_searchlist", type="boolean", nullable=true, options={"default" = 0})
     */
    private $has_recommended_slot_searchlist = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->upsells = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->clean_name = strtolower(str_replace(array('-', '.', ' ', '/'), '', $this->name));
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
     * Set slug.
     *
     * @param string $slug
     * @return Category
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
     * Set lft.
     *
     * @param integer $lft
     *
     * @return Category
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param integer $rgt
     *
     * @return Category
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set full_slug.
     *
     * @param text $full_slug
     *
     * @return Category
     */
    public function setFullSlug($full_slug)
    {
        $this->full_slug = $full_slug;

        return $this;
    }

    /**
     * Get full_slug.
     *
     * @return integer
     */
    public function getFullSlug()
    {
        return $this->full_slug;
    }

    /**
     * Set root.
     *
     * @param integer $root
     *
     * @return Category
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root.
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set lvl.
     *
     * @param integer $lvl
     *
     * @return Category
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl.
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Add children.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $children
     *
     * @return Category
     */
    public function addChild(\Fa\Bundle\EntityBundle\Entity\Category $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $children
     */
    public function removeChild(\Fa\Bundle\EntityBundle\Entity\Category $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\CategoryTranslation $translations
     * @return Category
     */
    public function addTranslation(\Fa\Bundle\EntityBundle\Entity\CategoryTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\CategoryTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\EntityBundle\Entity\CategoryTranslation $translations)
    {
        $this->translations->removeElement($translations);
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
     * Set parent.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $parent
     *
     * @return Category
     */
    public function setParent(\Fa\Bundle\EntityBundle\Entity\Category $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add entities.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $entities
     *
     * @return Category
     */
    public function addEntity(\Fa\Bundle\EntityBundle\Entity\Entity $entities)
    {
        $this->entities[] = $entities;

        return $this;
    }

    /**
     * Remove entities.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $entities
     */
    public function removeEntity(\Fa\Bundle\EntityBundle\Entity\Entity $entities)
    {
        $this->entities->removeElement($entities);
    }

    /**
     * Get entities.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return AdTempImage
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
     * Add upsells
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Upsell $upsells
     *
     * @return User
     */
    public function addUpsell(\Fa\Bundle\PromotionBundle\Entity\Upsell $upsells)
    {
        $this->upsells[] = $upsells;

        return $this;
    }

    /**
     * Remove upsells.
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Upsell $upsells
     */
    public function removeUpsell(\Fa\Bundle\PromotionBundle\Entity\Upsell $upsells)
    {
        $this->upsells->removeElement($upsells);
    }

    /**
     * Get upsells.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUpsells()
    {
        return $this->upsells->toArray();
    }

    /**
     * Set h1Tag.
     *
     * @param string $h1Tag
     *
     * @return Category
     */
    public function setH1Tag($h1Tag)
    {
        $this->h1_tag = $h1Tag;

        return $this;
    }

    /**
     * Get h1Tag.
     *
     * @return string
     */
    public function getH1Tag()
    {
        return $this->h1_tag;
    }

    /**
     * Set metaDescription.
     *
     * @param string $metaDescription
     *
     * @return Category
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
     * @return Category
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
     * @return Category
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
     * Set page description.
     *
     * @param string $pageDescription
     *
     * @return Category
     */
    public function setPageDescription($pageDescription)
    {
        $this->page_description = $pageDescription;

        return $this;
    }

    /**
     * Get page title.
     *
     * @return string
     */
    public function getPageDescription()
    {
        return $this->page_description;
    }

    /**
     * Get payment.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return $this->getName();
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
     * Set display on footer.
     *
     * @param boolean $display_on_footer
     *
     * @return Object
     */
    public function setDisplayOnFooter($display_on_footer)
    {
        $this->display_on_footer = $display_on_footer;

        return $display_on_footer;
    }

    /**
     * Get no follow.
     *
     * @return boolean
     */
    public function getDisplayOnFooter()
    {
        return $this->display_on_footer;
    }

    /**
     * Set synonyms keywords.
     *
     * @param string $synonymsKeywords
     *
     * @return Category
     */
    public function setSynonymsKeywords($synonymsKeywords)
    {
        $this->synonyms_keywords = $synonymsKeywords;

        return $this;
    }

    /**
     * Get synonyms keywords.
     *
     * @return string
     */
    public function getSynonymsKeywords()
    {
        return $this->synonyms_keywords;
    }

    /**
     * Set is_nimber_enabled.
     *
     * @param string $is_nimber_enabled
     * @return Category
     */
    public function setIsNimberEnabled($is_nimber_enabled)
    {
        $this->is_nimber_enabled = $is_nimber_enabled;

        return $this;
    }

    /**
     * Get is_nimber_enabled.
     *
     * @return string
     */
    public function getIsNimberEnabled()
    {
        return $this->is_nimber_enabled;
    }

    /**
     * Set nimber_size.
     *
     * @param string $nimber_size
     * @return Category
     */
    public function setNimberSize($nimber_size)
    {
        $this->nimber_size = $nimber_size;

        return $this;
    }

    /**
     * Get nimber_size.
     *
     * @return string
     */
    public function getNimberSize()
    {
        return $this->nimber_size;
    }

    /**
     * Set is_paa_disabled.
     *
     * @param string $is_paa_disabled
     * @return Category
     */
    public function setIsPaaDisabled($is_paa_disabled)
    {
    	$this->is_paa_disabled = $is_paa_disabled;

    	return $this;
    }

    /**
     * Get is_paa_disabled.
     *
     * @return string
     */
    public function getIsPaaDisabled()
    {
    	return $this->is_paa_disabled;
    }

    /**
     * Set is_finance_enabled.
     *
     * @param string $is_finance_enabled
     * @return Category
     */
    public function setIsFinanceEnabled($is_finance_enabled)
    {
     $this->is_finance_enabled = $is_finance_enabled;

     return $this;
    }

    /**
     * Get is_finance_enabled.
     *
     * @return string
     */
    public function getIsFinanceEnabled()
    {
     return $this->is_finance_enabled;
    }

    /**
     * Set finance title.
     *
     * @param string $financeTitle
     *
     * @return Category
     */
    public function setFinanceTitle($financeTitle)
    {
     $this->finance_title = $financeTitle;

     return $this;
    }

    /**
     * Get finance title.
     *
     * @return string
     */
    public function getFinanceTitle()
    {
     return $this->finance_title;
    }

    /**
     * Get finance url.
     *
     * @return string
     */
    public function getFinanceUrl()
    {
     return $this->finance_url;
    }

    /**
     * Set finance url.
     *
     * @param string $financeUrl
     *
     * @return Category
     */
    public function setFinanceUrl($financeUrl)
    {
     $this->finance_url = $financeUrl;

     return $this;
    }

    /**
     * Set is_oneclickenq_enabled.
     *
     * @param string $is_oneclickenq_enabled
     * @return Category
     */
    public function setIsOneclickenqEnabled($is_oneclickenq_enabled)
    {
     $this->is_oneclickenq_enabled = $is_oneclickenq_enabled;

     return $this;
    }

    /**
     * Get is_oneclickenq_enabled.
     *
     * @return string
     */
    public function getIsOneclickenqEnabled()
    {
     return $this->is_oneclickenq_enabled;
    }

    /**
     * Set has_recommended_slot.
     *
     * @param boolean $has_recommended_slot
     * @return Category
     */
    public function setHasRecommendedSlot($has_recommended_slot)
    {
        $this->has_recommended_slot = $has_recommended_slot;

        return $this;
    }

    /**
     * Get has_recommended_slot.
     *
     * @return boolean
     */
    public function getHasRecommendedSlot()
    {
        return $this->has_recommended_slot;
    }
    
    /**
     * Set is_featured_upgrade_enabled.
     *
     * @param string $is_featured_upgrade_enabled
     * @return Category
     */
    public function setIsFeaturedUpgradeEnabled($is_featured_upgrade_enabled)
    {
    	$this->is_featured_upgrade_enabled= $is_featured_upgrade_enabled;
    	
    	return $this;
    }
    
    /**
     * Get is_featured_upgrade_enabled.
     *
     * @return string
     */
    public function getIsFeaturedUpgradeEnabled()
    {
    	return $this->is_featured_upgrade_enabled;
    }
    
    /**
     * Get Featured Upgrade stats/info.
     *
     * @return string
     */
    public function getFeaturedUpgradeInfo()
    {
    	return $this->featured_upgrade_info;
    }
    
    /**
     * Set Featured Upgrade stats/info.
     *
     * @param string $featured_upgrade_info
     *
     * @return Category
     */
    public function setFeaturedUpgradeInfo($featured_upgrade_info)
    {
    	$this->featured_upgrade_info = $featured_upgrade_info;
    	
    	return $this;
    }
    
    /**
     * Get finance url.
     *
     * @return string
     */
    public function getFeaturedUpgradeBtnTxt()
    {
    	return $this->featured_upgrade_btn_txt;
    }
    
    /**
     * Set Featured Upgrade Button Txt.
     *
     * @param string $featured_upgrade_btn_txt
     *
     * @return Category
     */
    public function setFeaturedUpgradeBtnTxt($featured_upgrade_btn_txt)
    {
    	$this->featured_upgrade_btn_txt = $featured_upgrade_btn_txt;
    	
    	return $this;
    }

    /**
     * Set has_recommended_slot_searchlist.
     *
     * @param boolean $has_recommended_slot_searchlist
     * @return Category
     */

    public function setHasRecommendedSlotSearchlist($has_recommended_slot_searchlist)
    {
        $this->has_recommended_slot_searchlist = $has_recommended_slot_searchlist;

        return $this;
    }

    /**
     * Get has_recommended_slot_searchlist.
     *
     * @return boolean
     */
    public function getHasRecommendedSlotSearchlist()
    {
        return $this->has_recommended_slot_searchlist;
    }
}
