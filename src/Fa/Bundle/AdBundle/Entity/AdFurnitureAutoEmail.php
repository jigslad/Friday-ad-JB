<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store emial_sent_date.
 *
 * @author Konda Reddy <konda.reddy@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="furniture_auto")
 */
class AdFurnitureAutoEmail
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
     *  user id.
     *
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;


    /**
     * Email Sent Date.
     *
     * @var string
     *
     * @ORM\Column(name="email_sent_date", type="string", nullable=false)
     */
    private $email_sent_date;

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
     * Set user.
     *
     * @param \Fa\Bundle\AdBundle\Entity\User $user
     *
     * @return UserId
     */
    public function setUserId(\Fa\Bundle\AdBundle\Entity\User $user_id = null)
    {
        $this->user = $user_id;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Fa\Bundle\AdBundle\Entity\User
     */
    public function getUserId()
    {
        return $this->user;
    }

    /**
     * Set Email sent date.
     *
     * @param integer $emailSentDate
     *
     * @return EmailSentDate
     */
    public function setEmailSentDate($emailSentDate = null)
    {
        $this->email_sent_date = $emailSentDate;

        return $this;
    }

    /**
     * Get Email sent date.
     *
     * @return EmailSentDate
     */
    public function getEmailSentDate($emailSentDate)
    {
        return $this->email_sent_date;
    }
}
