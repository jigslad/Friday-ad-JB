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
 * This table is used to store category information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="message_spammer")
 * @ORM\Entity(repositoryClass="Fa\Bundle\MessageBundle\Repository\MessageSpammerRepository")
 */
class MessageSpammer
{
    /**
     * Id.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Ad id.
     *
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer")
     */
    private $ad_id;

    /**
     * Message.
     *
     * @var \Fa\Bundle\MessageBundle\Entity\Message
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\MessageBundle\Entity\Message")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $message;

    /**
     * Spammer.
     *
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="spammer_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $spammer;

     /**
      * Reporter.
      *
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="reporter_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $reporter;

    /**
     * Reason.
     *
     * @var string
     *
     * @ORM\Column(name="reason", type="text", nullable=true)
     */
    private $reason;

    /**
     * Status.
     *
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 0})
     */
    private $status;

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
     * Set reason.
     *
     * @param string $reason
     *
     * @return MessageSpammer
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return MessageSpammer
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
     * Set message.
     *
     * @param \Fa\Bundle\MessageBundle\Entity\Message $message
     *
     * @return MessageSpammer
     */
    public function setMessage(\Fa\Bundle\MessageBundle\Entity\Message $message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return \Fa\Bundle\MessageBundle\Entity\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set spammer.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $spammer
     *
     * @return MessageSpammer
     */
    public function setSpammer(\Fa\Bundle\UserBundle\Entity\User $spammer = null)
    {
        $this->spammer = $spammer;

        return $this;
    }

    /**
     * Get spammer.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getSpammer()
    {
        return $this->spammer;
    }

    /**
     * Set reporter.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $reporter
     *
     * @return MessageSpammer
     */
    public function setReporter(\Fa\Bundle\UserBundle\Entity\User $reporter = null)
    {
        $this->reporter = $reporter;

        return $this;
    }

    /**
     * Get reporter.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * Set ad_id.
     *
     * @param string $ad_id
     * @return AdReportDaily
     */
    public function setAdId($ad_id)
    {
        $this->ad_id = $ad_id;

        return $this;
    }

    /**
     * Get ad_id.
     *
     * @return string
     */
    public function getAdId()
    {
        return $this->ad_id;
    }
}
