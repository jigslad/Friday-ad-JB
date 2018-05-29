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
//use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This table is used to store category information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="message", indexes={@ORM\Index(name="fa_message_deleted_by_user1_index", columns={"deleted_by_user1"}), @ORM\Index(name="fa_message_deleted_by_user2_index", columns={"deleted_by_user2"}), @ORM\Index(name="fa_message_message_ad_id_index", columns={"message_ad_id"}), @ORM\Index(name="fa_message_is_oneclickenq_message_index", columns={"is_oneclickenq_message"}), @ORM\Index(name="fa_message_oneclickenq_reply_index", columns={"oneclickenq_reply"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\MessageBundle\Repository\MessageRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\MessageBundle\Listener\MessageListener" })
 */
class Message
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
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="text_message", type="text", nullable=true)
     */
    private $text_message;

    /**
     * @var string
     *
     * @ORM\Column(name="html_message", type="text", nullable=true)
     */
    private $html_message;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sender_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $sender;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_email", type="string", length=100, nullable=true)
     */
    private $sender_email;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_first_name", type="string", length=30, nullable=true)
     */
    private $sender_first_name;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_last_name", type="string", length=30, nullable=true)
     */
    private $sender_last_name;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="receiver_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $receiver;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_email", type="string", length=100, nullable=true)
     */
    private $receiver_email;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_first_name", type="string", length=30, nullable=true)
     */
    private $receiver_first_name;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_last_name", type="string", length=30, nullable=true)
     */
    private $receiver_last_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=100, nullable=true)
     */
    private $ip_address;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="smallint", options={"default" = 0}, nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_read", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_read = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="lft", type="integer", nullable=true)
     */
    private $lft;

    /**
     * @var integer
     *
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     */
    private $rgt;

    /**
     * @var integer
     *
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="lvl", type="integer", nullable=true, options={"default" = 0})
     */
    private $lvl = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\MessageBundle\Entity\Message", mappedBy="parent")
     * @ORM\OrderBy({
     *     "lft"="ASC"
     * })
     */
    private $children;

    /**
     * @var \Fa\Bundle\MessageBundle\Entity\Message
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\MessageBundle\Entity\Message", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment_path", type="string", nullable=true)
     */
    private $attachment_path;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment_file_name", type="string", length=30, nullable=true)
     */
    private $attachment_file_name;

    /**
     * @var string
     */
    protected $attachment;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment_org_file_name", type="string", length=255, nullable=true)
     */
    private $attachment_org_file_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="originator_id", type="integer", nullable=true)
     */
    private $originator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleted_by_user1", type="integer", nullable=true, options={"default" = 0})
     */
    private $deleted_by_user1 = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleted_by_user2", type="integer", nullable=true, options={"default" = 0})
     */
    private $deleted_by_user2 = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="message_ad_id", type="integer")
     */
    private $message_ad_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_oneclickenq_message", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_oneclickenq_message;

    /**
     * @var string
     *
     * @ORM\Column(name="oneclickenq_reply", type="string", length=10, nullable=true)
     */
    private $oneclickenq_reply;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_attachments", type="boolean", nullable=true, options={"default" = 0})
     */
    private $has_attachments = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_attachments_remove_message", type="boolean", nullable=true, options={"default" = 1})
     */
    private $show_attachments_remove_message = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_attachments_removed_message", type="boolean", nullable=true, options={"default" = 1})
     */
    private $show_attachments_removed_message = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_attachments_ignored_message", type="boolean", nullable=true, options={"default" = 0})
     */
    private $show_attachments_ignored_message = 0;

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
     * Set subject.
     *
     * @param string $subject
     *
     * @return Message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set text message.
     *
     * @param string $textMessage
     *
     * @return Message
     */
    public function setTextMessage($textMessage)
    {
        $this->text_message = $textMessage;

        return $this;
    }

    /**
     * Get text_message.
     *
     * @return string
     */
    public function getTextMessage()
    {
        return $this->text_message;
    }

    /**
     * Set html message.
     *
     * @param string $htmlMessage
     *
     * @return Message
     */
    public function setHtmlMessage($htmlMessage)
    {
        $this->html_message = $htmlMessage;

        return $this;
    }

    /**
     * Get html message.
     *
     * @return string
     */
    public function getHtmlMessage()
    {
        return $this->html_message;
    }

    /**
     * Set sender email.
     *
     * @param string $senderEmail
     *
     * @return Message
     */
    public function setSenderEmail($senderEmail)
    {
        $this->sender_email = $senderEmail;

        return $this;
    }

    /**
     * Get sender email.
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->sender_email;
    }

    /**
     * Set sender first name.
     *
     * @param string $senderFirstName
     *
     * @return Message
     */
    public function setSenderFirstName($senderFirstName)
    {
        $this->sender_first_name = $senderFirstName;

        return $this;
    }

    /**
     * Get sender first name.
     *
     * @return string
     */
    public function getSenderFirstName()
    {
        return $this->sender_first_name;
    }

    /**
     * Set sender last name.
     *
     * @param string $senderLastName
     *
     * @return Message
     */
    public function setSenderLastName($senderLastName)
    {
        $this->sender_last_name = $senderLastName;

        return $this;
    }

    /**
     * Get sender last name.
     *
     * @return string
     */
    public function getSenderLastName()
    {
        return $this->sender_last_name;
    }

    /**
     * Set receiver email.
     *
     * @param string $receiverEmail
     *
     * @return Message
     */
    public function setReceiverEmail($receiverEmail)
    {
        $this->receiver_email = $receiverEmail;

        return $this;
    }

    /**
     * Get receiver email.
     *
     * @return string
     */
    public function getReceiverEmail()
    {
        return $this->receiver_email;
    }

    /**
     * Set receiver first name.
     *
     * @param string $receiverFirstName
     *
     * @return Message
     */
    public function setReceiverFirstName($receiverFirstName)
    {
        $this->receiver_first_name = $receiverFirstName;

        return $this;
    }

    /**
     * Get receiver first name.
     *
     * @return string
     */
    public function getReceiverFirstName()
    {
        return $this->receiver_first_name;
    }

    /**
     * Set receiver last name.
     *
     * @param string $receiverLastName
     *
     * @return Message
     */
    public function setReceiverLastName($receiverLastName)
    {
        $this->receiver_last_name = $receiverLastName;

        return $this;
    }

    /**
     * Get receiver last name.
     *
     * @return string
     */
    public function getReceiverLastName()
    {
        return $this->receiver_last_name;
    }

    /**
     * Set is read.
     *
     * @param boolean $isRead
     *
     * @return Message
     */
    public function setIsRead($isRead)
    {
        $this->is_read = $isRead;

        return $this;
    }

    /**
     * Get is read.
     *
     * @return boolean
     */
    public function getIsRead()
    {
        return $this->is_read;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return Message
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set lft.
     *
     * @param integer $lft
     *
     * @return Message
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param integer $rgt
     *
     * @return Message
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     *
     * @return Message
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root.
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set lvl.
     *
     * @param integer $lvl
     *
     * @return Message
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl.
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set sender.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $sender
     *
     * @return Message
     */
    public function setSender(\Fa\Bundle\UserBundle\Entity\User $sender = null)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set receiver.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $receiver
     *
     * @return Message
     */
    public function setReceiver(\Fa\Bundle\UserBundle\Entity\User $receiver = null)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get receiver.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return Message
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad.
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Add children.
     *
     * @param \Fa\Bundle\MessageBundle\Entity\Message $children
     *
     * @return Message
     */
    public function addChild(\Fa\Bundle\MessageBundle\Entity\Message $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param \Fa\Bundle\MessageBundle\Entity\Message $children
     */
    public function removeChild(\Fa\Bundle\MessageBundle\Entity\Message $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent.
     *
     * @param \Fa\Bundle\MessageBundle\Entity\Message $parent
     *
     * @return Message
     */
    public function setParent(\Fa\Bundle\MessageBundle\Entity\Message $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \Fa\Bundle\MessageBundle\Entity\Message
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set value.
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
     * Get value.
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
     * @return Cart
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
     * Set ip address.
     *
     * @param string $ipAddress
     *
     * @return Message
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    /**
     * Get ip address.
     *
     * @return boolean
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * Set attachment path.
     *
     * @param string $attachmentPath
     *
     * @return Message
     */
    public function setAttachmentPath($attachmentPath)
    {
        $this->attachment_path = $attachmentPath;

        return $this;
    }

    /**
     * Get attachment path.
     *
     * @return string
     */
    public function getAttachmentPath()
    {
        return $this->attachment_path;
    }

    /**
     * Set attachment file name.
     *
     * @param string $attachmentFileName
     *
     * @return Message
     */
    public function setAttachmentFileName($attachmentFileName)
    {
        $this->attachment_file_name= $attachmentFileName;

        return $this;
    }

    /**
     * Get attachment path.
     *
     * @return string
     */
    public function getAttachmentFileName()
    {
        return $this->attachment_file_name;
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $attachment
     */
    public function setAttachment(UploadedFile $attachment = null)
    {
        $this->attachment = $attachment;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set attachment original file name.
     *
     * @param string $attachmentOrgFileName
     *
     * @return Message
     */
    public function setAttachmentOrgFileName($attachmentOrgFileName)
    {
        $this->attachment_org_file_name= $attachmentOrgFileName;

        return $this;
    }

    /**
     * Get attachment original file name.
     *
     * @return string
     */
    public function getAttachmentOrgFileName()
    {
        return $this->attachment_org_file_name;
    }

    /**
     * Set lft.
     *
     * @param integer $originator_id
     *
     * @return Message
     */
    public function setOriginatorId($originator_id)
    {
        $this->originator_id = $originator_id;

        return $this;
    }

    /**
     * Get originator id.
     *
     * @return integer
     */
    public function getOriginatorId()
    {
        return $this->originator_id;
    }

    /**
     * Get deleted by user 1 id.
     *
     * @return integer
     */
    public function getDeletedByUser1()
    {
        return $this->deleted_by_user1;
    }

    /**
     * Set deleted_by_user1.
     *
     * @param integer $deleted_by_user1
     *
     * @return Message
     */
    public function setDeletedByUser1($deleted_by_user1)
    {
        $this->deleted_by_user1 = $deleted_by_user1;

        return $this;
    }

    /**
     * Get deleted by user 2 id.
     *
     * @return integer
     */
    public function getDeletedByUser2()
    {
        return $this->deleted_by_user2;
    }

    /**
     * Set deleted_by_user2.
     *
     * @param integer $deleted_by_user2
     *
     * @return Message
     */
    public function setDeletedByUser2($deleted_by_user2)
    {
        $this->deleted_by_user2 = $deleted_by_user2;

        return $this;
    }

    /**
     * Set message_ad_id.
     *
     * @param string $message_ad_id
     * @return Message
     */
    public function setMessageAdId($message_ad_id)
    {
        $this->message_ad_id = $message_ad_id;

        return $this;
    }

    /**
     * Get message_ad_id.
     *
     * @return string
     */
    public function getMessageAdId()
    {
        return $this->message_ad_id;
    }

    /**
     * Set is_oneclickenq_message.
     *
     * @param string $is_oneclickenq_message
     * @return Category
     */
    public function setIsOneclickenqMessage($is_oneclickenq_message)
    {
     $this->is_oneclickenq_message = $is_oneclickenq_message;

     return $this;
    }

    /**
     * Get is_oneclickenq_message.
     *
     * @return string
     */
    public function getIsOneclickenqMessage()
    {
     return $this->is_oneclickenq_message;
    }

    /**
     * Set one click enq reply.
     *
     * @param string $oneclickenqReply
     *
     * @return Message
     */
    public function setOneclickenqReply($oneclickenqReply)
    {
     $this->oneclickenq_reply = $oneclickenqReply;

     return $this;
    }

    /**
     * Get one click enq reply.
     *
     * @return string
     */
    public function getOneclickenqReply()
    {
     return $this->oneclickenq_reply;
    }

    /**
     * Set is read.
     *
     * @param boolean $hasAttachments
     *
     * @return Message
     */
    public function setHasAttachments($hasAttachments)
    {
        $this->has_attachments = $hasAttachments;

        return $this;
    }

    /**
     * Get is read.
     *
     * @return boolean
     */
    public function getHasAttachments()
    {
        return $this->has_attachments;
    }

    /**
     * Set show attachments remove message.
     *
     * @param boolean $showAttachmentsRemoveMessage
     *
     * @return Message
     */
    public function setShowAttachmentsRemoveMessage($showAttachmentsRemoveMessage)
    {
        $this->show_attachments_remove_message = $showAttachmentsRemoveMessage;

        return $this;
    }

    /**
     * Get show attachments remove message.
     *
     * @return boolean
     */
    public function getShowAttachmentsRemoveMessage()
    {
        return $this->show_attachments_remove_message;
    }

    /**
     * Set show attachments removed message.
     *
     * @param boolean $showAttachmentsRemovedMessage
     *
     * @return Message
     */
    public function setShowAttachmentsRemovedMessage($showAttachmentsRemovedMessage)
    {
        $this->show_attachments_removed_message = $showAttachmentsRemovedMessage;

        return $this;
    }

    /**
     * Get show attachments removed message.
     *
     * @return boolean
     */
    public function getShowAttachmentsRemovedMessage()
    {
        return $this->show_attachments_removed_message;
    }

    /**
     * Set show attachments ignored message.
     *
     * @param boolean $showAttachmentsIgnoredMessage
     *
     * @return Message
     */
    public function setShowAttachmentsIgnoredMessage($showAttachmentsIgnoredMessage)
    {
        $this->show_attachments_ignored_message = $showAttachmentsIgnoredMessage;

        return $this;
    }

    /**
     * Get show attachments remove message.
     *
     * @return boolean
     */
    public function getShowAttachmentsIgnoredMessage()
    {
        return $this->show_attachments_ignored_message;
    }
}
