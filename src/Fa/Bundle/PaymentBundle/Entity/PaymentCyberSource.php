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
 * PaymentCyberSource.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="payment_cyber_source")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentCyberSourceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentCyberSource
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
     * @ORM\Column(name="request_id", type="string", length=255, nullable=true)
     */
    private $request_id;

    /**
     * @var string
     *
     * @ORM\Column(name="request_token", type="string", length=255, nullable=true)
     */
    private $request_token;

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
     * Set request_id
     *
     * @param string $requestId
     * @return PaymentCyberSource
     */
    public function setRequestId($requestId)
    {
        $this->request_id = $requestId;

        return $this;
    }

    /**
     * Get request_id
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * Set request_token
     *
     * @param string $requestToken
     * @return PaymentCyberSource
     */
    public function setRequestToken($requestToken)
    {
        $this->request_token = $requestToken;

        return $this;
    }

    /**
     * Get request_token
     *
     * @return string
     */
    public function getRequestToken()
    {
        return $this->request_token;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return PaymentCyberSource
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
     * @return PaymentCyberSource
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
     * @return PaymentCyberSource
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
     * @return PaymentCyberSource
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
}
