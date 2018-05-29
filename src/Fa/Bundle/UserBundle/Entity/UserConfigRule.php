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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * This table is used to store core configuration parameters of the system.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_config_rule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserConfigRuleRepository")
 * @UniqueEntity(fields={"user", "config"}, message="Configuration rule is already set for this user.", errorPath="value")
 * @ORM\EntityListeners({ "Fa\Bundle\UserBundle\Listener\UserConfigRuleListener" })
 * @ORM\HasLifecycleCallbacks
 */
class UserConfigRule
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
     * @var \Fa\Bundle\CoreBundle\Entity\Config
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\CoreBundle\Entity\Config")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="config_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $config;

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
     * @var string
     *
     * @Assert\NotBlank(message="Value is required.")
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="period_from", type="integer", length=10, nullable=true)
     */
    private $period_from;

    /**
     * @var integer
     *
     * @ORM\Column(name="period_to", type="integer", length=10, nullable=true)
     */
    private $period_to;

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
     * Constructor
     */
    public function __construct()
    {
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return UserConfigRule
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
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserConfigRule
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
     * Set status
     *
     * @param boolean $status
     * @return AdImage
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
     * Set created_at
     *
     * @param integer $createdAt
     * @return Ad
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
     * @return Ad
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
     * Set config
     *
     * @param \Fa\Bundle\CoreBundle\Entity\Config $config
     * @return UserConfigRule
     */
    public function setConfig(\Fa\Bundle\CoreBundle\Entity\Config $config = null)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config
     *
     * @return \Fa\Bundle\CoreBundle\Entity\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set period_from
     *
     * @param integer $periodFrom
     * @return UserConfigRule
     */
    public function setPeriodFrom($periodFrom)
    {
        $this->period_from = $periodFrom;

        return $this;
    }

    /**
     * Get period_from
     *
     * @return integer
     */
    public function getPeriodFrom()
    {
        return $this->period_from;
    }

    /**
     * Set period_to
     *
     * @param integer $periodTo
     * @return UserConfigRule
     */
    public function setPeriodTo($periodTo)
    {
        $this->period_to = $periodTo;

        return $this;
    }

    /**
     * Get period_to
     *
     * @return integer
     */
    public function getPeriodTo()
    {
        return $this->period_to;
    }
}
