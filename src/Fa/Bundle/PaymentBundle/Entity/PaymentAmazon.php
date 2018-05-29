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
 * PaymentAmazon.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2017 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="payment_amazon")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentAmazonRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentAmazon
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
     * @ORM\Column(name="confirm_request_id", type="string", length=255, nullable=true)
     */
    private $confirm_request_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorize_request_id", type="string", length=255, nullable=true)
     */
    private $authorize_request_id;

    /**
     * @var string
     *
     * @ORM\Column(name="amazon_authorization_id", type="string", length=255, nullable=true)
     */
    private $amazon_authorization_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorization_reference_id", type="string", length=255, nullable=true)
     */
    private $authorization_reference_id;

    /**
     * @var text
     *
     * @ORM\Column(name="billing_info", type="text", nullable=true)
     */
    private $billing_info;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string",  nullable=true)
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
     * @var string
     *
     * @ORM\Column(name="amazon_token", type="string",length=255, nullable=true)
     */
    private $amazon_token;

    
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
     * Set confirm_request_id
     *
     * @param string $confirmRequestId
     * @return PaymentAmazon
     */
    public function setConfirmRequestId($confirmRequestId)
    {
        $this->confirm_request_id = $confirmRequestId;

        return $this;
    }

    /**
     * Get confirm_request_id
     *
     * @return string
     */
    public function getConfirmRequestId()
    {
        return $this->confirm_request_id;
    }

    /**
     * Set authorize_request_id
     *
     * @param string $authorizeRequestId
     * @return PaymentAmazon
     */
    public function setAuthorizeRequestId($authorizeRequestId)
    {
        $this->authorize_request_id = $authorizeRequestId;

        return $this;
    }

    /**
     * Get authorize_request_id
     *
     * @return string
     */
    public function getAuthorizeRequestId()
    {
        return $this->authorize_request_id;
    }

    /**
     * Set amazon_authorization_id
     *
     * @param string $amazonAuthorizationId
     * @return PaymentAmazon
     */
    public function setAmazonAuthorizationId($amazonAuthorizationId)
    {
        $this->amazon_authorization_id = $amazonAuthorizationId;

        return $this;
    }

    /**
     * Get amazon_authorization_id
     *
     * @return string
     */
    public function getAmazonAuthorizationId()
    {
        return $this->amazon_authorization_id;
    }

    /**
     * Set authorization_reference_id
     *
     * @param string $authorizationReferenceId
     * @return PaymentAmazon
     */
    public function setAuthorizationReferenceId($authorizationReferenceId)
    {
        $this->authorization_reference_id = $authorizationReferenceId;

        return $this;
    }

    /**
     * Get authorization_reference_id
     *
     * @return string
     */
    public function getAuthorizationReferenceId()
    {
        return $this->authorization_reference_id;
    }

    /**
     * Set billing_info
     *
     * @param string $billingInfo
     * @return PaymentAmazon
     */
    public function setBillingInfo($billingInfo)
    {
        $this->billing_info = $billingInfo;

        return $this;
    }

    /**
     * Get billing_info
     *
     * @return string
     */
    public function getBillingInfo()
    {
        return $this->billing_info;
    }
    
    /**
     * Set ip
     *
     * @param string $ip
     * @return PaymentAmazon
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
     * @return PaymentAmazon
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
     * @return PaymentAmazon
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
     * @return PaymentAmazon
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
     * @return PaymentAmazon
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
     * Set AmazonToken
     *
     * @param string $amazon_token
     * @return PaymentAmazon
     */
    public function setAmazonToken($amazon_token)
    {
        $this->amazon_token = $amazon_token;

        return $this;
    }

    /**
     * Get AmazonToken
     *
     * @return string
     */
    public function getAmazonToken()
    {
        return $this->amazon_token;
    }
}
