<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to automated email report.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="automated_email_report_log", indexes={@ORM\Index(name="fa_report_automated_email_report_log_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_automated_email_report_log_identifier_index", columns={"identifier"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\AutomatedEmailReportLogRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AutomatedEmailReportLog
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
     * @var boolean
     *
     * @ORM\Column(name="is_opened", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_opened;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10, nullable=true)
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
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;


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
     * Set ad_id
     *
     * @param integer $adId
     * @return AdReportEnquiryDaily
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return AutomatedEmailReportLog
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
     * @return AutomatedEmailReportLog
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
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

    /**
     * Set identifier.
     *
     * @param string $identifier
     * @return AutomatedEmailReportLog
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
     * Set email.
     *
     * @param string $email
     * @return AutomatedEmailReportLog
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
     * Set email_sent_counter.
     *
     * @param string $is_opened
     * @return AutomatedEmailReportLog
     */
    public function setIsOpened($is_opened)
    {
        $this->is_opened = $is_opened;

        return $this;
    }

    /**
     * Get email_sent_counter.
     *
     * @return string
     */
    public function getIsOpened()
    {
        return $this->is_opened;
    }
}
