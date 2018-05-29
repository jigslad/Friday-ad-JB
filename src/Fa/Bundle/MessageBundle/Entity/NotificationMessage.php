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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This table is used to store notification messages.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="notification_message")
 * @ORM\Entity(repositoryClass="Fa\Bundle\MessageBundle\Repository\NotificationMessageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class NotificationMessage
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
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="notification_type", type="string", length=100, nullable=true)
     */
    private $notification_type;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="boolean", options={"default" = 0}, nullable=true)
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
     * @ORM\Column(name="is_flash", type="boolean", options={"default" = 0}, nullable=true)
     */
    private $is_flash;

    /**
     * @var string
     *
     * @ORM\Column(name="text_message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=25, nullable=true)
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="indentifier", type="string", length=255, nullable=true)
     * @Gedmo\Slug(fields={"name"}, updatable=false, separator="_")
     */
    private $indentifier;

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
     * Set name.
     *
     * @param string $name
     * @return NotificationMessage
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set notification type.
     *
     * @param string $notificationType
     *
     * @return HeaderImage
     */
    public function setNotificationType($notificationType)
    {
        $this->notification_type = $notificationType;

        return $this;
    }

    /**
     * Get notification type.
     *
     * @return string
     */
    public function getNotificationType()
    {
        return $this->notification_type;
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
     * Set message.
     *
     * @param string $message
     * @return NotificationMessage
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set duration.
     *
     * @param string $duration
     * @return NotificationMessage
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration.
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set indentifier.
     *
     * @param string $indentifier
     * @return NotificationMessage
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
     * Set created_at.
     *
     * @param integer $created_at
     * @return NotificationMessage
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
     * @return NotificationMessage
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

}
