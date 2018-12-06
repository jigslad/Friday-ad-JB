<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store notification messages.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="notification_message_event", indexes={@ORM\Index(name="idx_ad_id", columns={"ad_id"}), @ORM\Index(name="idx_user_id", columns={"user_id"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\MessageBundle\Repository\NotificationMessageEventRepository")
 * @ORM\HasLifecycleCallbacks
 */
class NotificationMessageEvent
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
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer", nullable=true)
     */
    private $ad_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="indentifier", type="string", length=100, nullable=true)
     */
    private $indentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", length=100, nullable=true)
     */
    private $user_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_from", type="integer", length=10, nullable=true)
     */
    private $display_from;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", length=10, nullable=true)
     */
    private $expires_at;

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
     * @var string
     *
     * @ORM\Column(name="is_flash", type="boolean", options={"default" = 0}, nullable=true)
     */
    private $is_flash;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="boolean", options={"default" = 1}, nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="is_dismissable", type="boolean", options={"default" = 0}, nullable=true)
     */
    private $is_dismissable;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return Message
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ad_id.
     *
     * @param integer $ad_id
     * @return NotificationMessageEvent
     */
    public function setAdId($ad_id)
    {
        $this->ad_id = $ad_id;

        return $this;
    }

    /**
     * Get ad_id.
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
    }

    /**
     * Set user_id.
     *
     * @param integer $user_id
     * @return NotificationMessageEvent
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set indentifier.
     *
     * @param string $indentifier
     * @return NotificationMessageEvent
     */
    public function setIndentifier($indentifier)
    {
        $this->indentifier = $indentifier;

        return $this;
    }

    /**
     * Get indentifier.
     *
     * @return string
     */
    public function getIndentifier()
    {
        return $this->indentifier;
    }

    /**
     * Set user_name.
     *
     * @param string $user_name
     *
     * @return NotificationMessageEvent
     */
    public function setUserName($user_name)
    {
        $this->user_name = $user_name;

        return $this;
    }

    /**
     * Get user_name.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set expires_at.
     *
     * @param integer $expires_at
     * @return NotificationMessageEvent
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    /**
     * Get expires_at.
     *
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set display_from.
     *
     * @param integer $display_from
     * @return NotificationMessageEvent
     */
    public function setDisplayFrom($display_from)
    {
        $this->display_from = $display_from;

        return $this;
    }

    /**
     * Get display_from.
     *
     * @return integer
     */
    public function getDisplayFrom()
    {
        return $this->display_from;
    }

    /**
     * Set created_at.
     *
     * @param integer $created_at
     * @return NotificationMessageEvent
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at.
     *
     * @param integer $updated_at
     * @return NotificationMessageEvent
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Get updated_at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set is_flash.
     *
     * @param boolean $is_flash
     * @return NotificationMessage
     */
    public function setIsFlash($is_flash)
    {
        $this->is_flash = $is_flash;

        return $this;
    }

    /**
     * Get is_flash.
     *
     * @return boolean
     */
    public function getIsFlash()
    {
        return $this->is_flash;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     * @return NotificationMessage
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

    /**
     * Set is_dismissable.
     *
     * @param boolean $is_dismissable
     * @return NotificationMessage
     */
    public function setIsDismissable($is_dismissable)
    {
        $this->is_dismissable = $is_dismissable;

        return $this;
    }

    /**
     * Get is_dismissable.
     *
     * @return boolean
     */
    public function getIsDismissable()
    {
        return $this->is_dismissable;
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
}
