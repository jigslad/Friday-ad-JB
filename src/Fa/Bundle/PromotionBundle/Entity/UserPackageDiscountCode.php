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
 * This table is used to user package discount code information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_package_discount_code")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\UserPackageDiscountCodeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserPackageDiscountCode
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
     * @var \Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_discount_code_id", referencedColumnName="id")
     * })
     */
    private $package_discount_code;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var \Fa\Bundle\PaymentBundle\Entity\Payment
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     * })
     */
    private $payment;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer", nullable=true)
     */
    private $ad_id;

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
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return Payment
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set payment
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\Payment $payment
     * @return UserPackageDiscountCode
     */
    public function setPayment(\Fa\Bundle\PaymentBundle\Entity\Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment
     *
     * @return \Fa\Bundle\PaymentBundle\Entity\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set package discount code
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode $packageDiscountCode
     * @return UserPackageDiscountCode
     */
    public function setPackageDiscountCode(\Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode $packageDiscountCode = null)
    {
        $this->package_discount_code = $packageDiscountCode;

        return $this;
    }

    /**
     * Get package discount code
     *
     * @return \Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode
     */
    public function getPackageDiscountCode()
    {
        return $this->package_discount_code;
    }

    /**
     * Set ad_id.
     *
     * @param integer $ad_id
     * @return DotmailerInfo
     */
    public function setAdId($ad_id)
    {
        $this->ad_id = $ad_id;

        return $this;
    }

    /**
     * Get ad_id.
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
    }
}
