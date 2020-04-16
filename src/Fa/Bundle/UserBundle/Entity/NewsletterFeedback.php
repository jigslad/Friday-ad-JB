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

/**
 * This table is used to store information related to dotmailer.
 *
 * @author Gaurav Aggarwal <gaurav.aggarwal@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="newsletter_feedback", indexes={@ORM\Index(name="fa_newsletter_feedback_email_index", columns={"email"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\NewsletterFeedbackRepository")
 * @ORM\HasLifecycleCallbacks
 */
class NewsletterFeedback
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
	 * @ORM\Column(name="email", type="string", length=255, nullable=false)
	 */
	private $email;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="guid", type="string", length=100, nullable=false)
	 */
	private $guid;
	
	/**
	 * @var int
	 *
	 * @ORM\Column(name="reason", type="integer", length=1, nullable=false)
	 */
	private $reason;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="other_reason", type="string", length=1000, nullable=true)
	 */
	private $otherReason;
	
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
	 * Set email.
	 *
	 * @param string $email
	 * @return NewsletterFeedback
	 */
	public function setEmail($email)
	{
		$this->email = $email;
		
		return $this;
	}
	
	/**
	 * Get email.
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}
	
	/**
	 * Set guid.
	 *
	 * @param string $guid
	 * @return NewsletterFeedback
	 */
	public function setGuid($guid)
	{
		$this->guid = $guid;
		
		return $this;
	}
	
	/**
	 * Get guid.
	 *
	 * @return string
	 */
	public function getGuid()
	{
		return $this->guid;
	}
	
	/**
	 * Set reason.
	 *
	 * @param int $reason
	 * @return NewsletterFeedback
	 */
	public function setReason($reason)
	{
		$this->reason= $reason;
		
		return $this;
	}
	
	/**
	 * Get reason.
	 *
	 * @return int
	 */
	public function getReason()
	{
		return $this->reason;
	}
	
	/**
	 * Set other_reason.
	 *
	 * @param string $other_reason
	 * @return NewsletterFeedback
	 */
	public function setOtherReason($other_reason)
	{
		$this->otherReason= $other_reason;
		
		return $this;
	}
	
	/**
	 * Get other_reason.
	 *
	 * @return string
	 */
	public function getOtherReason()
	{
		return $this->otherReason;
	}
	
	/**
	 * Set created_at.
	 *
	 * @param integer $created_at
	 * @return NewsletterFeedback
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
	 * @return NewsletterFeedback
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
?>
