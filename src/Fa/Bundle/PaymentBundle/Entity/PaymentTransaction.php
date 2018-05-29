<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentTransaction.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="payment_transaction")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentTransactionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentTransaction
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
     * @ORM\Column(name="transaction_id", type="string", length=40, unique=true, nullable=true)
     */
    private $transaction_id;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="vat", type="float", precision=15, scale=2, nullable=true)
     */
    private $vat;

    /**
     * @var float
     *
     * @ORM\Column(name="vat_amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $vat_amount;

    /**
     * @var float
     *
     * @ORM\Column(name="discount_amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $discount_amount;

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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $user;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $ad;

    /**
     * @var \Fa\Bundle\PaymentBundle\Entity\Payment
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $payment;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_future_publish_at", type="integer", length=11, nullable=true)
     */
    private $ad_future_publish_at;

    /**
     * Set created at value.
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
     * @ORM\PreUpdate
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
     * Set transactionId
     *
     * @param string $transactionId
     *
     * @return PaymentTransaction
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return PaymentTransaction
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set discountAmount
     *
     * @param float $discountAmount
     *
     * @return PaymentTransaction
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discount_amount = $discountAmount;

        return $this;
    }

    /**
     * Get discountAmount
     *
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->discount_amount;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return PaymentTransaction
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param integer $updatedAt
     *
     * @return PaymentTransaction
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
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
     * @return PaymentTransaction
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
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return PaymentTransaction
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set payment
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\Payment $payment
     *
     * @return PaymentTransaction
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
     * Set vat
     *
     * @param float $amount
     *
     * @return Transaction
     */
    public function setVat($vat)
    {
        $this->vat = $vat;

        return $this;
    }

    /**
     * Get vat
     *
     * @return float
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set vat amount
     *
     * @param float $vat_amount
     *
     * @return Transaction
     */
    public function setVatAmount($vat_amount)
    {
        $this->vat_amount = $vat_amount;

        return $this;
    }

    /**
     * Get vat amount
     *
     * @return float
     */
    public function getVatAmount()
    {
        return $this->vat_amount;
    }

    /**
     * Set ad_future_publish_at.
     *
     * @param string $future_publish_at
     * @return Ad
     */
    public function setAdFuturePublishAt($ad_future_publish_at)
    {
        $this->ad_future_publish_at = $ad_future_publish_at;

        return $this;
    }

    /**
     * Get ad_future_publish_at.
     *
     * @return string
     */
    public function getAdFuturePublishAt()
    {
        return $this->ad_future_publish_at;
    }

}
