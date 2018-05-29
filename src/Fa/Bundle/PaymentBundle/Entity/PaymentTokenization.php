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
 * @ORM\Table(name="payment_tokenization")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PaymentBundle\Repository\PaymentTokenizationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentTokenization
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
     * @ORM\Column(name="subscription_id", type="string", length=50, nullable=true)
     */
    private $subscription_id;

    /**
     * @var string
     *
     * @ORM\Column(name="card_number", type="string", length=10, nullable=true)
     */
    private $card_number;

    /**
     * @var string
     *
     * @ORM\Column(name="card_holder_name", type="string", length=50, nullable=true)
     */
    private $card_holder_name;

    /**
     * @var string
     *
     * @ORM\Column(name="card_type", type="string", length=10, nullable=true)
     */
    private $card_type;

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
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

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
     * Set subscription_id
     *
     * @param string $subscription_id
     *
     * @return PaymentTokenization
     */
    public function setSubscriptionId($subscription_id)
    {
        $this->subscription_id = $subscription_id;

        return $this;
    }

    /**
     * Get subscription_id
     *
     * @return string
     */
    public function getSubscriptionId()
    {
        return $this->subscription_id;
    }

    /**
     * Set card_number
     *
     * @param integer $card_number
     *
     * @return PaymentTokenization
     */
    public function setCardNumber($card_number)
    {
        $this->card_number = $card_number;

        return $this;
    }

    /**
     * Get card_number
     *
     * @return string
     */
    public function getCardNumber()
    {
        return $this->card_number;
    }

    /**
     * Set card_holder_name
     *
     * @param string $card_holder_name
     *
     * @return PaymentTokenization
     */
    public function setCardHolderName($card_holder_name)
    {
        $this->card_holder_name = $card_holder_name;

        return $this;
    }

    /**
     * Get card_holder_name
     *
     * @return string
     */
    public function getCardHolderName()
    {
        return $this->card_holder_name;
    }

    /**
     * Set card_type
     *
     * @param string $card_type
     *
     * @return PaymentTokenization
     */
    public function setCardType($card_type)
    {
        $this->card_type = $card_type;

        return $this;
    }

    /**
     * Get card_type
     *
     * @return string
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set payment_method
     *
     * @param string $payment_method
     *
     * @return PaymentTokenization
     */
    public function setPaymentMethod($payment_method)
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    /**
     * Get payment_method
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Set created_at
     *
     * @param integer $created_at
     *
     * @return PaymentTokenization
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

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
     * @param integer $updated_at
     *
     * @return PaymentTokenization
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

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
     * Set value
     *
     * @param string $value
     *
     * @return PaymentTokenization
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
}
