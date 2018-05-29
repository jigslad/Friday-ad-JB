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
 * Payment
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="payment")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Payment
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
     * @ORM\Column(name="cart_code", type="string", length=40, nullable=true)
     */
    private $cart_code;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=8, nullable=true)
     */
    private $currency;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="discount_amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $discount_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=30, nullable=true)
     */
    private $payment_method;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="action_by_user_id", type="integer", nullable=true)
     */
    private $action_by_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_action_by_admin", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_action_by_admin;

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
     * @var \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_method_option_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     */
    private $delivery_method_option;

    /**
     * @var integer
     *
     * @ORM\Column(name="seller_user_id", type="integer", nullable=true)
     */
    private $seller_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="buy_now_status_id", type="smallint", length=4, nullable=true)
     */
    private $buy_now_status_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_review_reminder_sent", type="smallint", length=4, nullable=true)
     */
    private $is_review_reminder_sent;

    /**
     * @var integer
     *
     * @ORM\Column(name="change_status_at", type="integer", length=10, nullable=true)
     */
    private $change_status_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ga_status", type="boolean", nullable=true, options={"default" = 0})
     */
    private $ga_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="skip_payment_reason", type="string", length=255, nullable=true)
     */
    private $skip_payment_reason;

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
     * Set cartCode
     *
     * @param string $cartCode
     *
     * @return Payment
     */
    public function setCartCode($cartCode)
    {
        $this->cart_code = $cartCode;

        return $this;
    }

    /**
     * Get cartCode
     *
     * @return string
     */
    public function getCartCode()
    {
        return $this->cart_code;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Payment
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
     * Set currency
     *
     * @param string $currency
     *
     * @return Payment
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Payment
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
     * @return Payment
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
     * Set paymentMethod
     *
     * @param string $paymentMethod
     *
     * @return Payment
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->payment_method = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Payment
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
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return Payment
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
     * @return Payment
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
     * Set change_status_at
     *
     * @param integer $changeStatusAt
     *
     * @return Payment
     */
    public function setChangeStatusAt($changeStatusAt)
    {
        $this->change_status_at = $changeStatusAt;

        return $this;
    }

    /**
     * Get change_status_at
     *
     * @return integer
     */
    public function getChangeStatusAt()
    {
        return $this->change_status_at;
    }

    /**
     * Set change_status_at
     *
     * @param integer $changeStatusAt
     *
     * @return Payment
     */
    public function setIsReviewReminderSent($isReviewReminderSent)
    {
        $this->is_review_reminder_sent = $isReviewReminderSent;

        return $this;
    }

    /**
     * Get is_review_reminder_sent
     *
     * @return integer
     */
    public function getIsReviewReminderSent()
    {
        return $this->is_review_reminder_sent;
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
     * Set deliveryMethodOption
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption $deliveryMethodOption
     *
     * @return Payment
     */
    public function setDeliveryMethodOption(\Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption $deliveryMethodOption = null)
    {
        $this->delivery_method_option = $deliveryMethodOption;

        return $this;
    }

    /**
     * Get deliveryMethodOption
     *
     * @return \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption
     */
    public function getDeliveryMethodOption()
    {
        return $this->delivery_method_option;
    }

    /**
     * Set actionByUserId
     *
     * @param integer $actionByUserId
     *
     * @return PaymentTransaction
     */
    public function setActionByUserId($actionByUserId)
    {
        $this->action_by_user_id = $actionByUserId;

        return $this;
    }

    /**
     * Get actionByUserId
     *
     * @return integer
     */
    public function getActionByUserId()
    {
        return $this->action_by_user_id;
    }

    /**
     * Set isActionByAdmin
     *
     * @param boolean $isActionByAdmin
     *
     * @return PaymentTransaction
     */
    public function setIsActionByAdmin($isActionByAdmin)
    {
        $this->is_action_by_admin = $isActionByAdmin;

        return $this;
    }

    /**
     * Get isActionByAdmin
     *
     * @return boolean
     */
    public function getIsActionByAdmin()
    {
        return $this->is_action_by_admin;
    }

    /**
     * Set seller_user_id.
     *
     * @param integer $seller_user_id
     * @return Payment
     */
    public function setSellerUserId($seller_user_id)
    {
        $this->seller_user_id = $seller_user_id;

        return $this;
    }

    /**
     * Get seller_user_id.
     *
     * @return integer
     */
    public function getSellerUserId()
    {
        return $this->seller_user_id;
    }

    /**
     * Set buy_now_status_id.
     *
     * @param integer $buy_now_status_id
     * @return Payment
     */
    public function setBuyNowStatusId($buy_now_status_id)
    {
        $this->buy_now_status_id = $buy_now_status_id;

        return $this;
    }

    /**
     * Get buy_now_status_id.
     *
     * @return integer
     */
    public function getBuyNowStatusId()
    {
        return $this->buy_now_status_id;
    }

    /**
     * Set ga_status.
     *
     * @param string $ga_status
     * @return Payment
     */
    public function setGaStatus($ga_status)
    {
        $this->ga_status = $ga_status;

        return $this;
    }

    /**
     * Get ga_status.
     *
     * @return string
     */
    public function getGaStatus()
    {
        return $this->ga_status;
    }

    /**
     * Set skip_payment_reason.
     *
     * @param string $skip_payment_reason
     * @return Payment
     */
    public function setSkipPaymentReason($skip_payment_reason)
    {
        $this->skip_payment_reason = $skip_payment_reason;

        return $this;
    }

    /**
     * Get skip_payment_reason.
     *
     * @return string
     */
    public function getSkipPaymentReason()
    {
        return $this->skip_payment_reason;
    }
}
