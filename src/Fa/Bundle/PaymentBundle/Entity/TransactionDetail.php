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
 * TransactionDetail.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="transaction_detail")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\TransactionDetailRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\PaymentBundle\Listener\TransactionDetailListener" })
 */
class TransactionDetail
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
     * @ORM\Column(name="payment_for", type="string", length=4, nullable=true)
     */
    private $payment_for;

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
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var \Fa\Bundle\PaymentBundle\Entity\Transaction
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\Transaction")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="transaction_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $transaction;

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
     * @return TransactionDetail
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
     * @return TransactionDetail
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
     * @return TransactionDetail
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
     * Set transaction
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\Transaction $transaction
     *
     * @return TransactionDetail
     */
    public function setTransaction(\Fa\Bundle\PaymentBundle\Entity\Transaction $transaction = null)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transaction
     *
     * @return \Fa\Bundle\PaymentBundle\Entity\Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
