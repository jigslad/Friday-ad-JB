<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;

/**
 * This table is used to package information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="package")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\PackageRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\PromotionBundle\Entity\PackageTranslation")
 * @ORM\EntityListeners({ "Fa\Bundle\PromotionBundle\Listener\PackageListener" })
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class Package
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
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @Gedmo\Versioned
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Please enter package name.")
     * @Gedmo\Versioned
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_title", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Please enter package sub title.")
     * @Gedmo\Versioned
     */
    private $sub_title;

    /**
     * @var string
     *
     * @ORM\Column(name="new_ad_cta", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @Gedmo\Versioned
     */
    private $new_ad_cta;

    /**
     * @var string
     *
     * @ORM\Column(name="renewal_ad_cta", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @Gedmo\Versioned
     */
    private $renewal_ad_cta;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Translatable
     * @assert\NotBlank(message="Please enter package description.")
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="upgrade_description", type="text", nullable=true)
     * @Gedmo\Translatable
     */
    private $upgrade_description;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     * @Gedmo\Versioned
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="admin_price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     * @Gedmo\Versioned
     */
    private $admin_price;

    /**
     * @var string
     *
     * @ORM\Column(name="package_text", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $package_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_sr_no", type="smallint", nullable=true)
     * @Gedmo\Versioned
     */
    private $package_sr_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_for", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $package_for;

    /**
     * @var float
     *
     * @ORM\Column(name="insertion_price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Regex(pattern="/^[0-9.]+$/i", message="The value {{ value }} is not a valid float value.")
     * @Assert\Length(max = 12, maxMessage = "Insertion price cannot be longer than {{ limit }} digits long.")
     */
    private $insertion_price;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Fa\Bundle\PromotionBundle\Entity\Upsell")
     * @ORM\JoinTable(name="package_upsell",
     *   joinColumns={
     *     @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="upsell_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     * @Gedmo\Versioned
     */
    private $upsells;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=10, nullable=true)
     * @Assert\Regex(pattern="/^[a-z0-9 ]+$/i", message="The value {{ value }} is not a valid alpha numeric.")
     * @Assert\Length(max = 8, maxMessage = "Duration cannot be longer than {{ limit }} characters long.")
     * @Gedmo\Versioned
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $value;

    /**
     * @var integer
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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\PromotionBundle\Entity\PackageTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @assert\NotBlank(message="Please select package status.")
     * @Gedmo\Versioned
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="trail", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $trail;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Role
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $role;

    /**
     * @var \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EmailBundle\Entity\EmailTemplate")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="email_template_id", referencedColumnName="id", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $email_template;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="shop_category_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $shop_category;

    /**
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=255, nullable=true)
     */
    private $category_name;

    /**
     * @var string
     */
    private $filter_title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_admin_package", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_admin_package = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set created at value.
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

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
     * Set title
     *
     * @param string $title
     * @return Package
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
     * Get title as name
     *
     * @return string
     */
    public function getName()
    {
        return $this->title;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return Package
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set sub title
     *
     * @param string $subTitle
     * @return Package
     */
    public function setSubTitle($subTitle)
    {
        $this->sub_title = $subTitle;

        return $this;
    }

    /**
     * Get sub title
     *
     * @return string
     */
    public function getSubTitle()
    {
        return $this->sub_title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Package
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
     * Set upgrade description
     *
     * @param string $upgrade_description
     * @return Package
     */
    public function setUpgradeDescription($upgrade_description)
    {
        $this->upgrade_description = $upgrade_description;

        return $this;
    }

    /**
     * Get upgrade description
     *
     * @return string
     */
    public function getUpgradeDescription()
    {
        return $this->upgrade_description;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return Package
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Package
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
     * @return Package
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

    /**
     * Set status
     *
     * @param boolean $status
     * @return Package
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set trail
     *
     * @param boolean $trail
     * @return Package
     */
    public function setTrail($trail)
    {
        $this->trail = $trail;

        return $this;
    }

    /**
     * Get trail
     *
     * @return boolean
     */
    public function getTrail()
    {
        return $this->trail;
    }

    /**
     * Add upsells
     *
     * @param \Fa\Bundle\UserBundle\Entity\Upsell $upsells
     * @return Package
     */
    public function addUpsell(\Fa\Bundle\PromotionBundle\Entity\Upsell $upsells)
    {
        $this->upsells[] = $upsells;

        return $this;
    }

    /**
     * Remove upsells
     *
     * @param \Fa\Bundle\UserBundle\Entity\Upsell $upsells
     */
    public function removeUpsell(\Fa\Bundle\PromotionBundle\Entity\Upsell $upsells)
    {
        $this->upsells->removeElement($upsells);
    }

    /**
     * Get upsells
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUpsells()
    {
        return $this->upsells;
    }

    /**
     * Add translations
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\PackageTranslation $translations
     * @return Package
     */
    public function addTranslation(\Fa\Bundle\PromotionBundle\Entity\PackageTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\PackageTranslation $translations
     */
    public function removeTranslation(\Fa\Bundle\PromotionBundle\Entity\PackageTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Set role
     *
     * @param \Fa\Bundle\UserBundle\Entity\Role $role
     * @return Package
     */
    public function setRole(\Fa\Bundle\UserBundle\Entity\Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Fa\Bundle\UserBundle\Entity\Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set duration
     *
     * @param string duration
     * @return Package
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set package for
     *
     * @param string package_for
     * @return Package
     */
    public function setPackageFor($package_for)
    {
        $this->package_for = $package_for;

        return $this;
    }

    /**
     * Get package for
     *
     * @return string
     */
    public function getPackageFor()
    {
        return $this->package_for;
    }

    /**
     * Set package_text
     *
     * @param string package_for
     * @return Package
     */
    public function setPackageText($package_text)
    {
        $this->package_text = $package_text;

        return $this;
    }

    /**
     * Get package_text
     *
     * @return string
     */
    public function getPackageText()
    {
        return $this->package_text;
    }

    /**
     * Set category
     *
     * @param string category
     * @return Package
     */
    public function setShopCategory($shop_category)
    {
        $this->shop_category = $shop_category;

        return $this;
    }

    /**
     * Get shop category
     *
     * @return string
     */
    public function getShopCategory()
    {
        return $this->shop_category;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Transaction
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set Set email template
     *
     * @param Fa\Bundle\EmailBundle\Entity\EmailTemplate $email_template
     * @return Package
     */
    public function setEmailTemplate(\Fa\Bundle\EmailBundle\Entity\EmailTemplate $email_template = null)
    {
        $this->email_template = $email_template;

        return $this;
    }

    /**
     * Get role
     *
     * @return Fa\Bundle\EmailBundle\Entity\EmailTemplate
     */
    public function getEmailTemplate()
    {
        return $this->email_template;
    }

    /**
     * Set package_sr_no.
     *
     * @param string $package_sr_no
     * @return Package
     */
    public function setPackageSrNo($package_sr_no)
    {
        $this->package_sr_no = $package_sr_no;

        return $this;
    }

    /**
     * Get package_sr_no.
     *
     * @return string
     */
    public function getPackageSrNo()
    {
        return $this->package_sr_no;
    }

    /**
     * Set category_name.
     *
     * @param string $category_name
     * @return Package
     */
    public function setCategoryName($category_name)
    {
        $this->category_name = $category_name;

        return $this;
    }

    /**
     * Get category_name.
     *
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * Get filter_title.
     *
     * @return string
     */
    public function getFilterTitle()
    {
        return $this->category_name.': '.$this->title;
    }

    /**
     * Set admin_price.
     *
     * @param float $admin_price
     * @return Package
     */
    public function setAdminPrice($admin_price)
    {
        $this->admin_price = $admin_price;

        return $this;
    }

    /**
     * Get admin_price.
     *
     * @return float
     */
    public function getAdminPrice()
    {
        return $this->admin_price;
    }

    /**
     * Set is_admin_package.
     *
     * @param string $is_admin_package
     * @return Package
     */
    public function setIsAdminPackage($is_admin_package)
    {
        $this->is_admin_package = $is_admin_package;

        return $this;
    }

    /**
     * Get is_admin_package.
     *
     * @return string
     */
    public function getIsAdminPackage()
    {
        return $this->is_admin_package;
    }

    /**
     * Set new_ad_cta.
     *
     * @param string $new_ad_cta
     * @return Package
     */
    public function setNewAdCta($new_ad_cta)
    {
        $this->new_ad_cta = $new_ad_cta;

        return $this;
    }

    /**
     * Get new_ad_cta.
     *
     * @return string
     */
    public function getNewAdCta()
    {
        return $this->new_ad_cta;
    }

    /**
     * Set renewal_ad_cta.
     *
     * @param string $renewal_ad_cta
     * @return Package
     */
    public function setRenewalAdCta($renewal_ad_cta)
    {
        $this->renewal_ad_cta = $renewal_ad_cta;

        return $this;
    }

    /**
     * Get renewal_ad_cta.
     *
     * @return string
     */
    public function getRenewalAdCta()
    {
        return $this->renewal_ad_cta;
    }
}
