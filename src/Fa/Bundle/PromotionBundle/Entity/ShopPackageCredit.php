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

/**
 * This table is used to store user credits information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="shop_package_credit")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\ShopPackageCreditRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ShopPackageCredit
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
     * @var \Fa\Bundle\UserBundle\Entity\Package
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Package", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $package;

    /**
     * @var string
     *
     * @ORM\Column(name="credit", type="string", length=20, nullable=false)
     */
    private $credit;

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
     * @var boolean
     *
     * @ORM\Column(name="paid_user_only", type="boolean", nullable=true, options={"default" = 0})
     */
    private $paid_user_only = false;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=5, nullable=true)
     */
    private $duration;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

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
     * Set credit.
     *
     * @param string $credit
     * @return UserCredit
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * Get credit.
     *
     * @return string
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * Set package
     *
     * @param \Fa\Bundle\UserBundle\Entity\Package $package
     * @return PackageRule
     */
    public function setPackage(\Fa\Bundle\PromotionBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Fa\Bundle\UserBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set duration
     *
     * @param string duration
     * @return Upsell
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
}
