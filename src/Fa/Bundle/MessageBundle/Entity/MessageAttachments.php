<?php

namespace Fa\Bundle\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\MessageBundle\Entity\MessageAttachments
 *
 * This table is used to store message attachements information.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="message_attachements", indexes={@ORM\Index(name="fa_message_attachments_message_id_index", columns={"message_id"}), @ORM\Index(name="fa_message_attachments_session_id_index", columns={"session_id"}), @ORM\Index(name="fa_message_attachments_is_image_index", columns={"is_image"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\MessageBundle\Repository\MessageAttachmentsRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class MessageAttachments
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
     * @var \Fa\Bundle\MessageBundle\Entity\Message
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\MessageBundle\Entity\Message")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=100, nullable=true)
     */
    private $session_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=100)
     * @Gedmo\Versioned
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="original_file_name", type="string", length=255, nullable=true)
     */
    private $original_file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="mime_type", type="string", length=100, nullable=true)
     */
    private $mime_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="size", type="integer", length=10, nullable=true)
     */
    private $size;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_image", type="boolean", nullable=true, options={"default" = 1})
     */
    private $is_image = 0;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\MessageBundle\Entity\Message $message
     * @return MessageAttachments
     */
    public function setMessage(\Fa\Bundle\MessageBundle\Entity\Message $message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\MessageBundle\Entity\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set session_id
     *
     * @param string $sessionId
     * @return MessageAttachments
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;

        return $this;
    }

    /**
     * Get session_id
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return MessageAttachements
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return MessageAttachements
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set original file name
     *
     * @param string $original_file_name
     * @return MessageAttachements
     */
    public function setOriginalFileName($original_file_name)
    {
        $this->original_file_name = $original_file_name;

        return $this;
    }

    /**
     * Get original_file_name
     *
     * @return string
     */
    public function getOriginalFileName()
    {
        return $this->original_file_name;
    }

    /**
     * Set mime_type
     *
     * @param string $mime_type
     * @return MessageAttachements
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;

        return $this;
    }

    /**
     * Get mime_type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Set size
     *
     * @param string $size
     * @return MessageAttachements
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set is image.
     *
     * @param boolean $isImage
     *
     * @return MessageAttachments
     */
    public function setIsImage($isImage)
    {
        $this->is_image = $isImage;

        return $this;
    }

    /**
     * Get is image.
     *
     * @return boolean
     */
    public function getIsImage()
    {
        return $this->is_image;
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
     * Set created_at
     *
     * @param integer $createdAt
     * @return MessageAttachements
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
     * @return MessageAttachements
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
}
