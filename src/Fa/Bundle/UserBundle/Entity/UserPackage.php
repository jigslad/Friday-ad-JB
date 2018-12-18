<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store site information of user.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_package")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserPackageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserPackage
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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * @var \Fa\Bundle\PromotionBundle\Entity\Package
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Package")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $package;

    /**
     * @var integer
     *
     * @ORM\Column(name="renewed_at", type="integer", nullable=true)
     */
    private $renewed_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", nullable=true)
     */
    private $expires_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="closed_at", type="integer", nullable=true)
     */
    private $closed_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_auto_renew", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_auto_renew;

    /**
     * @var integer
     *
     * @ORM\Column(name="cancelled_at", type="integer", nullable=true)
     */
    private $cancelled_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_renewal_mail_sent", type="boolean", nullable=true)
     */
    private $is_renewal_mail_sent;

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
     * @var boolean
     *
     * @ORM\Column(name="status", type="string", length=2, nullable=true)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="remark", type="text", nullable=true)
     */
    private $remark;

    /**
     * @var \Fa\Bundle\PaymentBundle\Entity\Payment
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $payment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="trial", type="boolean", nullable=true, options={"default" = 0})
     */
    private $trial;

    /**
     * @var integer
     *
     * @ORM\Column(name="source", type="smallint", nullable=true)
     */
    private $source;

    /**
     * @var integer
     *
     * @ORM\Column(name="boost_overide", type="integer", length=10, nullable=true)
     */
    private $boost_overide;

    /**
     * Get status.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return UserPackage
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get pacakge.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set pacakge.
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Package $package
     *
     * @return UserPackage
     */
    public function setPackage(\Fa\Bundle\PromotionBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Fa\Bundle\PromotionBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set renewed_at.
     *
     * @param integer $renewed_at
     *
     * @return UserPackage
     */
    public function setRenewedAt($renewed_at)
    {
        $this->renewed_at = $renewed_at;

        return $this;
    }

    /**
     * Get renewed_at.
     *
     * @return integer
     */
    public function getRenewedAt()
    {
        return $this->renewed_at;
    }

    /**
     * Set expires at.
     *
     * @param integer $expires_at
     *
     * @return UserPackage
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    /**
     * Get renewed_at.
     *
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set is_renewal_mail_sent.
     *
     * @param integer $is_renewal_mail_sent
     *
     * @return UserPackage
     */
    public function setIsRenewalMailSent($is_renewal_mail_sent)
    {
        $this->is_renewal_mail_sent = $is_renewal_mail_sent;

        return $this;
    }

    /**
     * Get is_renewal_mail_sent.
     *
     * @return integer
     */
    public function getIsRenewalMailSent()
    {
        return $this->is_renewal_mail_sent;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     * @return UserPackage
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
     * Set trial.
     *
     * @param boolean $trial
     * @return UserPackage
     */
    public function setTrial($trial)
    {
        $this->trial = $trial;

        return $this;
    }

    /**
     * Get trial.
     *
     * @return boolean
     */
    public function getTrial()
    {
        return $this->trial;
    }

    /**
     * Set created_at.
     *
     * @param integer $createdAt
     * @return UserPackage
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set closed_at.
     *
     * @param integer $closedAt
     * @return UserPackage
     */
    public function setClosedAt($closedAt)
    {
        $this->closed_at = $closedAt;

        return $this;
    }

    /**
     * Get closed_at.
     *
     * @return integer
     */
    public function getClosedAt()
    {
        return $this->closed_at;
    }

    /**
     * Get cancelled_at.
     *
     * @return integer
     */
    public function getCancelledAt()
    {
        return $this->cancelled_at;
    }

    /**
     * Set cancelled_at.
     *
     * @param integer $cancelledAt
     * @return UserPackage
     */
    public function setCancelledAt($cancelledAt)
    {
        $this->cancelled_at = $cancelledAt;

        return $this;
    }

    /**
     * Set updated_at.
     *
     * @param integer $updatedAt
     * @return UserPackage
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set remark.
     *
     * @param string $remark
     *
     * @return UserPackage
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
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
     * Set payment
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\Payment $payment
     * @return PaymentCyberSource
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
     * Set is_auto_renew.
     *
     * @param boolean $is_auto_renew
     * @return UserPackage
     */
    public function setIsAutoRenew($is_auto_renew)
    {
        $this->is_auto_renew = $is_auto_renew;

        return $this;
    }

    /**
     * Get is_auto_renew.
     *
     * @return boolean
     */
    public function getIsAutoRenew()
    {
        return $this->is_auto_renew;
    }

    /**
     * Set boost_overide.
     *
     * @param integer $boost_overide
     * @return UserPackage
    */
    public function setBoostOveride($boost_overide)
    {
        $this->boost_overide = $boost_overide;

        return $this;
    }

    /**
     * Get boost_overide.
     *
     * @return integer
     */
    public function getBoostOveride()
    {
        return $this->boost_overide;
    }
}
