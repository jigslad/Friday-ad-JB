<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\PaaLiteEmailNotification
 *
 * This table is used to store ad image information.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright  2018 Friday Media Group Ltd.
 *
 * @ORM\Table(name="paa_lite_email_notification")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PaaLiteEmailNotificationRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class PaaLiteEmailNotification
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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $user;

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
     * @ORM\Column(name="is_paa_lite_registered_user", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_paa_lite_registered_user;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_registered_mail_sent", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_registered_mail_sent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ad_confirmation_mail_sent", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_ad_confirmation_mail_sent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_ad_confirmation_notification_sent", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_ad_confirmation_notification_sent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_registration_notification_sent", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_registration_notification_sent;

    
    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return PaaLiteEmailNotification
    */

    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return PaaLiteEmailNotification
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
     * @param \Fa\Bundle\UserBundle\Entity\Ad $ad
     * @return PaaLiteEmailNotification
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\UserBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return PaaLiteEmailNotification
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get CreatedAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set Is PaaLite Registered User
     *
     * @param boolean $is_paa_lite_registered_user
     * @return PaaLiteEmailNotification
     */
    public function setIsPaaLiteRegisteredUser($is_paa_lite_registered_user)
    {
        $this->is_paa_lite_registered_user = $is_paa_lite_registered_user;

        return $this;
    }

    /**
     * Get is required
     *
     * @return boolean
     */
    public function getIsPaaLiteRegisteredUser()
    {
        return $this->is_paa_lite_registered_user;
    }

    /**
     * Set Is Registered Mail Sent
     *
     * @param boolean $is_registered_mail_sent
     * @return PaaLiteEmailNotification
     */
    public function setIsRegisteredMailSent($is_registered_mail_sent)
    {
        $this->is_registered_mail_sent = $is_registered_mail_sent;

        return $this;
    }

    /**
     * Get is required
     *
     * @return boolean
     */
    public function getIsRegisteredMailSent()
    {
        return $this->is_registered_mail_sent;
    }

    /**
     * Set Is ad confirmation mail sent
     *
     * @param boolean $is_ad_confirmation_mail_sent
     * @return PaaLiteEmailNotification
     */
    public function setIsAdConfirmationMailSent($is_ad_confirmation_mail_sent)
    {
        $this->is_ad_confirmation_mail_sent = $is_ad_confirmation_mail_sent;

        return $this;
    }

    /**
     * Get is ad confirmation mail sent
     *
     * @return boolean
     */
    public function getIsAdConfirmationMailSent()
    {
        return $this->is_ad_confirmation_mail_sent;
    }

    /**
     * Set Is Ad Confirmation Notification Sent
     *
     * @param boolean $is_ad_confirmation_notification_sent
     * @return PaaLiteEmailNotification
     */
    public function setIsAdConfirmationNotificationSent($is_ad_confirmation_notification_sent)
    {
        $this->is_ad_confirmation_notification_sent = $is_ad_confirmation_notification_sent;

        return $this;
    }

    /**
     * Get is required
     *
     * @return boolean
     */
    public function getIsAdConfirmationNotificationSent()
    {
        return $this->is_ad_confirmation_notification_sent;
    }

    /**
     * Set Is Registeration Notification Sent
     *
     * @param boolean $is_registration_notification_sent
     * @return PaaLiteEmailNotification
     */
    public function setIsRegisterationNotificationSent($is_registration_notification_sent)
    {
        $this->is_registration_notification_sent = $is_registration_notification_sent;

        return $this;
    }

    /**
     * Get is required
     *
     * @return boolean
     */
    public function getIsRegisterationNotificationSent()
    {
        return $this->is_registration_notification_sent;
    }

}
