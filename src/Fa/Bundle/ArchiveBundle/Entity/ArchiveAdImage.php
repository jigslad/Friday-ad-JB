<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ArchiveBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store ad image information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="archive_ad_image")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ArchiveBundle\Repository\ArchiveAdImageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ArchiveAdImage
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
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=100)
     */
    private $hash;

    /**
     * @var integer
     *
     * @ORM\Column(name="ord", type="smallint", length=4)
     */
    private $ord;

    /**
     * @var string
     *
     * @ORM\Column(name="video", type="string", length=10, nullable=true)
     */
    private $video;

    /**
     * @var string
     *
     * @ORM\Column(name="video_url", type="string", length=255, nullable=true)
     */
    private $video_url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

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
     * @var \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ArchiveBundle\Entity\ArchiveAd")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="archive_ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $archive_ad;

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=100, nullable=true)
     */
    private $session_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=50, nullable=true)
     */
    private $update_type;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_ref", type="string", length=255, nullable=true)
     */
    private $ad_ref;

    /**
     * @var string
     *
     * @ORM\Column(name="old_path", type="text", length=500, nullable=true)
     */
    private $old_path;

    /**
     * Set created at value.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
     *
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
     * Set path.
     *
     * @param string $path
     * @return ArchiveAdImage
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set hash.
     *
     * @param string $hash
     * @return ArchiveAdImage
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

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
     * Set ord.
     *
     * @param integer $ord
     * @return ArchiveAdImage
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord.
     *
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set video.
     *
     * @param string $video
     * @return ArchiveAdImage
     */
    public function setVideo($video)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video.
     *
     * @return string
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set video url.
     *
     * @param string $videoUrl
     * @return ArchiveAdImage
     */
    public function setVideoUrl($videoUrl)
    {
        $this->video_url = $videoUrl;

        return $this;
    }

    /**
     * Get video url.
     *
     * @return string
     */
    public function getVideoUrl()
    {
        return $this->video_url;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     * @return ArchiveAdImage
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
     * Set created at.
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
     * @return Ad
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
     * Set archive ad.
     *
     * @param \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd $archive_ad
     * @return ArchiveAdImage
     */
    public function setArchiveAd(\Fa\Bundle\ArchiveBundle\Entity\ArchiveAd $archive_ad = null)
    {
        $this->archive_ad = $archive_ad;

        return $this;
    }

    /**
     * Get archive ad.
     *
     * @return \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd
     */
    public function getArchiveAd()
    {
        return $this->archive_ad;
    }

    /**
     * Set session id.
     *
     * @param string $sessionId
     * @return AdFavorite
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;

        return $this;
    }

    /**
     * Get session id.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set update_type
     *
     * @param string $update_type
     * @return Ad
     */
    public function setUpdateType($update_type)
    {
        $this->update_type = $update_type;

        return $this;
    }

    /**
     * Get update_type
     *
     * @return string
     */
    public function getUpdateType()
    {
        return $this->update_type;
    }

    /**
     * Set trans_id
     *
     * @param string $trans_id
     *
     * @return AdLocation
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;

        return $this;
    }

    /**
     * Get trans_id
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * Set ad_ref
     *
     * @param string $ad_ref
     * @return AdImage
     */
    public function setAdRef($ad_ref)
    {
        $this->ad_ref = $ad_ref;

        return $this;
    }

    /**
     * Get ad_ref
     *
     * @return string
     */
    public function getAdRef()
    {
        return $this->ad_ref;
    }

    /**
     * Set old_path
     *
     * @param string $old_path
     * @return AdImage
     */
    public function setOldPath($old_path)
    {
        $this->old_path = $old_path;

        return $this;
    }

    /**
     * Get old_path
     *
     * @return string
     */
    public function getOldPath()
    {
        return $this->old_path;
    }
}
