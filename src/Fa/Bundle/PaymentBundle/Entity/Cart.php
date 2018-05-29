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
 * Cart.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="cart")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\CartRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Cart
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
     * @ORM\Column(name="cart_code", type="string", length=40, nullable=true, unique=true)
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
     * @ORM\Column(name="payment_method", type="string", length=20, nullable=true)
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
     * @var boolean
     *
     * @ORM\Column(name="is_buy_now", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_buy_now = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_shop_package_purchase", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_shop_package_purchase = 0;

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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * @return Cart
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
     * Set is_buy_now
     *
     * @param boolean $is_buy_now
     *
     * @return Cart
     */
    public function setIsBuyNow($is_buy_now)
    {
        $this->is_buy_now = $is_buy_now;

        return $this;
    }

    /**
     * Get is_buy_now
     *
     * @return boolean
     */
    public function getIsBuyNow()
    {
        return $this->is_buy_now;
    }

    /**
     * Set is_shop_package_purchase.
     *
     * @param boolean $is_shop_package_purchase
     * @return Cart
     */
    public function setIsShopPackagePurchase($is_shop_package_purchase)
    {
        $this->is_shop_package_purchase = $is_shop_package_purchase;

        return $this;
    }

    /**
     * Get is_shop_package_purchase.
     *
     * @return boolean
     */
    public function getIsShopPackagePurchase()
    {
        return $this->is_shop_package_purchase;
    }
}
