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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * This table is used to package discount code information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="package_discount_code")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\PackageDiscountCodeRepository")
 * @UniqueEntity(fields="code", message="Code already exist.")
 * @ORM\HasLifecycleCallbacks
 */
class PackageDiscountCode
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
     * @ORM\Column(name="code", type="string", length=20, nullable=false)
     */
    private $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount_type", type="smallint", nullable=false)
     */
    private $discount_type;

    /**
     * @var string
     *
     * @ORM\Column(name="discount_value", type="string", length=255, nullable=true)
     */
    private $discount_value;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_sr_no", type="string", nullable=true)
     */
    private $package_sr_no;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="role_ids", type="string", length=20, nullable=true)
     */
    private $role_ids;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid_user_only", type="boolean", nullable=true, options={"default" = 0})
     */
    private $paid_user_only = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

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
     * @var text
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", length=10, nullable=true)
     */
    private $expires_at;

    /**
     * @var text
     *
     * @ORM\Column(name="emails", type="text", nullable=true)
     */
    private $emails;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_limit", type="integer", length=10, nullable=true)
     */
    private $total_limit;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_limit", type="integer", length=10, nullable=true)
     */
    private $user_limit;

    /**
     * @var integer
     *
     * @ORM\Column(name="monthly_user_limit", type="integer", length=10, nullable=true)
     */
    private $monthly_user_limit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin_only_package", type="boolean", nullable=true, options={"default" = 0})
     */
    private $admin_only_package = false;

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
     * Set created_at
     *
     * @param integer $createdAt
     * @return PackageRule
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
     * @return PackageRule
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
     * @return PackageRule
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
     * Set category
     *
     * @param \Fa\Bundle\UserBundle\Entity\Category $category
     * @return PackageRule
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\UserBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return PackageDiscountCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set discount_type.
     *
     * @param string $discount_type
     * @return PackageDiscountCode
     */
    public function setDiscountType($discount_type)
    {
        $this->discount_type = $discount_type;

        return $this;
    }

    /**
     * Get discount_type.
     *
     * @return string
     */
    public function getDiscountType()
    {
        return $this->discount_type;
    }

    /**
     * Set discount_value.
     *
     * @param string $discount_value
     * @return PackageDiscountCode
     */
    public function setDiscountValue($discount_value)
    {
        $this->discount_value = $discount_value;

        return $this;
    }

    /**
     * Get discount_value.
     *
     * @return string
     */
    public function getDiscountValue()
    {
        return $this->discount_value;
    }

    /**
     * Set role_ids.
     *
     * @param string $role_ids
     * @return PackageDiscountCode
     */
    public function setRoleIds($role_ids)
    {
        $this->role_ids = $role_ids;

        return $this;
    }

    /**
     * Get role_ids.
     *
     * @return string
     */
    public function getRoleIds()
    {
        return $this->role_ids;
    }

    /**
     * Set paid_user_only.
     *
     * @param string $paid_user_only
     * @return PackageDiscountCode
     */
    public function setPaidUserOnly($paid_user_only)
    {
        $this->paid_user_only = $paid_user_only;

        return $this;
    }

    /**
     * Get paid_user_only.
     *
     * @return string
     */
    public function getPaidUserOnly()
    {
        return $this->paid_user_only;
    }

    /**
     * Set package_sr_no.
     *
     * @param string $package_sr_no
     * @return PackageDiscountCode
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
     * Set description.
     *
     * @param string $description
     * @return PackageDiscountCode
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
     * Set expires_at.
     *
     * @param integer $expires_at
     * @return PackageDiscountCode
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    /**
     * Get expires_at.
     *
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set emails.
     *
     * @param string $emails
     * @return PackageDiscountCode
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;

        return $this;
    }

    /**
     * Get emails.
     *
     * @return string
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Set total_limit.
     *
     * @param string $total_limit
     * @return PackageDiscountCode
     */
    public function setTotalLimit($total_limit)
    {
        $this->total_limit = $total_limit;

        return $this;
    }

    /**
     * Get total_limit.
     *
     * @return string
     */
    public function getTotalLimit()
    {
        return $this->total_limit;
    }

    /**
     * Set user_limit.
     *
     * @param string $user_limit
     * @return PackageDiscountCode
     */
    public function setUserLimit($user_limit)
    {
        $this->user_limit = $user_limit;

        return $this;
    }

    /**
     * Get user_limit.
     *
     * @return string
     */
    public function getUserLimit()
    {
        return $this->user_limit;
    }

    /**
     * Set monthly_user_limit.
     *
     * @param string $monthly_user_limit
     * @return PackageDiscountCode
     */
    public function setMonthlyUserLimit($monthly_user_limit)
    {
        $this->monthly_user_limit = $monthly_user_limit;

        return $this;
    }

    /**
     * Get monthly_user_limit.
     *
     * @return string
     */
    public function getMonthlyUserLimit()
    {
        return $this->monthly_user_limit;
    }

    /**
     * Set admin_only_package.
     *
     * @param string $admin_only_package
     * @return PackageDiscountCode
     */
    public function setAdminOnlyPackage($admin_only_package)
    {
        $this->admin_only_package = $admin_only_package;

        return $this;
    }

    /**
     * Get admin_only_package.
     *
     * @return string
     */
    public function getAdminOnlyPackage()
    {
        return $this->admin_only_package;
    }
}
