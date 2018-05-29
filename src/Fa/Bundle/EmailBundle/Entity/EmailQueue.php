<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to semd email in queue.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="email_queue")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EmailBundle\Repository\EmailQueueRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EmailQueue
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
     * @ORM\Column(name="identifier", type="string", length=255)
     */
    private $identifier;

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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;


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
     * Set created at value.
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return EmailQueue
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
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return EmailQueue
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
     * @return EmailQueue
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
     * Set identifier.
     *
     * @param string $identifier
     * @return EmailQueue
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
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
     * Set status.
     *
     * @param boolean $status
     *
     * @return EmailTemplate
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }
}
