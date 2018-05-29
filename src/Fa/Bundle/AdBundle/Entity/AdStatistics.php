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

/**
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_statistics")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdStatisticsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdStatistics
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
     * @ORM\Column(name="renewed_at", type="integer", nullable=true)
     */
    private $renewed_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", nullable=true)
     */
    private $expires_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_renewal_mail_sent", type="boolean", nullable=true)
     */
    private $is_renewal_mail_sent;

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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

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
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set renewed at.
     *
     * @param integer $renewedAt
     *
     * @return AdStatistics
     */
    public function setRenewedAt($renewedAt)
    {
        $this->renewed_at = $renewedAt;

        return $this;
    }

    /**
     * Get renewed at.
     *
     * @return integer
     */
    public function getRenewedAt()
    {
        return $this->renewed_at;
    }

    /**
     * Set expires at.
     *
     * @param integer $expiresAt
     *
     * @return AdStatistics
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expires_at = $expiresAt;

        return $this;
    }

    /**
     * Get expires at.
     *
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set is renewal mail sent.
     *
     * @param boolean $isRenewalMailSent
     *
     * @return AdStatistics
     */
    public function setIsRenewalMailSent($isRenewalMailSent)
    {
        $this->is_renewal_mail_sent = $isRenewalMailSent;

        return $this;
    }

    /**
     * Get is renewal mail sent.
     *
     * @return boolean
     */
    public function getIsRenewalMailSent()
    {
        return $this->is_renewal_mail_sent;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return AdStatistics
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
     * Set updated at.
     *
     * @param integer $updatedAt
     *
     * @return AdStatistics
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set ad.
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return AdStatistics
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
}
