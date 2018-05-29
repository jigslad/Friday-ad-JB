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
 * PaymentPaypal.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="payment_paypal")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentPaypalRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentPaypal
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
     * @ORM\Column(name="external_ref_number", type="string", length=40, nullable=true)
     */
    private $external_ref_number;

    /**
     * @var string
     *
     * @ORM\Column(name="express_token", type="string", length=40, nullable=true)
     */
    private $express_token;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_id", type="string", length=20, nullable=true)
     */
    private $payer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=20, nullable=true)
     */
    private $ip;

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
     * @var \Fa\Bundle\PaymentBundle\Entity\Payment
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\Payment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payment_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $payment;


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
     * Set external_ref_number
     *
     * @param string $externalRefNumber
     * @return PaymentPaypal
     */
    public function setExternalRefNumber($externalRefNumber)
    {
        $this->external_ref_number = $externalRefNumber;

        return $this;
    }

    /**
     * Get external_ref_number
     *
     * @return string
     */
    public function getExternalRefNumber()
    {
        return $this->external_ref_number;
    }

    /**
     * Set express_token
     *
     * @param string $expressToken
     * @return PaymentPaypal
     */
    public function setExpressToken($expressToken)
    {
        $this->express_token = $expressToken;

        return $this;
    }

    /**
     * Get express_token
     *
     * @return string
     */
    public function getExpressToken()
    {
        return $this->express_token;
    }

    /**
     * Set payer_id
     *
     * @param string $payerId
     * @return PaymentPaypal
     */
    public function setPayerId($payerId)
    {
        $this->payer_id = $payerId;

        return $this;
    }

    /**
     * Get payer_id
     *
     * @return string
     */
    public function getPayerId()
    {
        return $this->payer_id;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return PaymentPaypal
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return PaymentPaypal
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
     * Set created_at
     *
     * @param integer $createdAt
     * @return PaymentPaypal
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
     * @return PaymentPaypal
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
     * Set payment
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\Payment $payment
     * @return PaymentPaypal
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
}
