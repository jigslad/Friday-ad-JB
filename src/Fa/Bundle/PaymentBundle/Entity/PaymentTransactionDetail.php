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
 * PaymentTransactionDetail.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="payment_transaction_detail")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentTransactionDetailRepository")
 */
class PaymentTransactionDetail
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
     * @ORM\Column(name="payment_for", type="string", length=8, nullable=true)
     */
    private $payment_for;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var float
     *
     * @ORM\Column(name="discount_amount", type="float", precision=15, scale=2, nullable=true)
     */
    private $discount_amount;

    /**
     * @var \Fa\Bundle\PaymentBundle\Entity\PaymentTransaction
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\PaymentTransaction")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_transaction_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $payment_transaction;

    /**
     * @var string
     *
     * @ORM\Column(name="ti_package", type="string", length=255, nullable=true)
     */
    private $ti_package;

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
     * Set paymentFor
     *
     * @param string $paymentFor
     *
     * @return PaymentTransactionDetail
     */
    public function setPaymentFor($paymentFor)
    {
        $this->payment_for = $paymentFor;

        return $this;
    }

    /**
     * Get paymentFor
     *
     * @return string
     */
    public function getPaymentFor()
    {
        return $this->payment_for;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return PaymentTransactionDetail
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
     * @return PaymentTransactionDetail
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
     * Set paymentTransaction
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\PaymentTransaction $paymentTransaction
     *
     * @return PaymentTransactionDetail
     */
    public function setPaymentTransaction(\Fa\Bundle\PaymentBundle\Entity\PaymentTransaction $paymentTransaction = null)
    {
        $this->payment_transaction = $paymentTransaction;

        return $this;
    }

    /**
     * Get paymentTransaction
     *
     * @return \Fa\Bundle\PaymentBundle\Entity\PaymentTransaction
     */
    public function getPaymentTransaction()
    {
        return $this->payment_transaction;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return TransactionDetail
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
     * Set ti_package.
     *
     * @param string $ti_package
     * @return AdUserPackage
     */
    public function setTiPackage($ti_package)
    {
        $this->ti_package = $ti_package;

        return $this;
    }

    /**
     * Get ti_package.
     *
     * @return string
     */
    public function getTiPackage()
    {
        return $this->ti_package;
    }
}
