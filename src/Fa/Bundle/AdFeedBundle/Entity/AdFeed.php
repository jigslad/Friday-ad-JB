<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_feed", indexes={@ORM\Index(name="fa_feed_trans_id",  columns={"trans_id"}), @ORM\Index(name="fa_feed_unique_idx", columns={"unique_id"}), @ORM\Index(name="fa_feed_ref_site_id",  columns={"ref_site_id"}), @ORM\Index(name="fa_feed_status",  columns={"status"}), @ORM\Index(name="fa_feed_is_updated",  columns={"is_updated"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\AdFeedRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdFeed
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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_id", type="string", length=255)
     */
    private $unique_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ref_site_id", type="integer")
     */
    private $ref_site_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=255, nullable=true)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="image_hash", type="string", length=255, nullable=true)
     */
    private $image_hash;

    /**
     * @var string
     *
     * @ORM\Column(name="user_hash", type="string", length=255, nullable=true)
     */
    private $user_hash;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="text", nullable=true)
     */
    private $remark;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_text", type="text", nullable=true)
     */
    private $ad_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=1)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_modified", type="datetime", nullable=true)
     */
    private $last_modified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_updated", type="boolean", options={"default" = 0})
     */
    private $is_updated = 0;

    /**
     * Get id.
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Set ad.
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return AdFeed
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return AdFeed
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get trans id.
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * Set trans id.
     *
     * @param string $trans_id
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;
        return $this;
    }

    /**
     * Get unique id.
     *
     * @return string
     */
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * Set unique_id.
     *
     * @param string $unique_id
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setUniqueId($unique_id)
    {
        $this->unique_id = $unique_id;
        return $this;
    }

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set hash.
     *
     * @param string $hash
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Get image hash.
     *
     * @return string
     */
    public function getImageHash()
    {
        return $this->image_hash;
    }

    /**
     * Set image hash.
     *
     * @param string $image_hash
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setImageHash($image_hash)
    {
        $this->image_hash = $image_hash;
        return $this;
    }

    /**
     * Get image hash.
     *
     * @return string
     */
    public function getUserHash()
    {
        return $this->user_hash;
    }

    /**
     * Set image hash.
     *
     * @param string $user_hash
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setUserHash($user_hash)
    {
        $this->user_hash = $user_hash;
        return $this;
    }

    /**
     * Get remark.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set remark.
     *
     * @param string $remark
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }

    /**
     * Get ref site id.
     *
     * @return string
     */
    public function getRefSiteId()
    {
        return $this->ref_site_id;
    }

    /**
     * Set ad_text.
     *
     * @param string $ad_text
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setAdText($ad_text)
    {
        $this->ad_text = $ad_text;
        return $this;
    }

    /**
     * Get ad_text.
     *
     * @return string
     */
    public function getAdText()
    {
        return $this->ad_text;
    }

    /**
     * Set ref site id.
     *
     * @param string $ref_site_id
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setRefSiteId($ref_site_id)
    {
        $this->ref_site_id = $ref_site_id;
        return $this;
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
     * @return Ad
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
     * @return Ad
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
     * Get status.
     *
     * @return the string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get is updated.
     *
     * @return the string
     */
    public function getIsUpdated()
    {
        return $this->is_updated;
    }

    /**
     * Set status.
     *
     * @param string $is_updated
     */
    public function setIsUpdated($is_updated)
    {
        $this->is_updated = $is_updated;
        return $this;
    }

    /**
     * Get last modified
     *
     * @return string
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Set modified since.
     *
     * @param \DateTime $type
     *
     */
    public function setLastModified(\DateTime $last_modified)
    {
        $this->last_modified = $last_modified;
        return $this;
    }
}
