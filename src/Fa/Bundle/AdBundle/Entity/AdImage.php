<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdImage
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_image", indexes={@ORM\Index(name="fa_ad_images_trans_id",  columns={"trans_id"}), @ORM\Index(name="fa_ad_image_ad_ref",  columns={"ad_ref"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdImageRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 * @ORM\EntityListeners({ "Fa\Bundle\AdBundle\Listener\AdImageListener" })
 */
class AdImage
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
     * @Gedmo\Versioned
     */
    private $hash;

    /**
     * @var integer
     *
     * @ORM\Column(name="ord", type="smallint", length=4)
     * @Gedmo\Versioned
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
     * @var string
     *
     * @ORM\Column(name="image_name", type="string", length=255, nullable=true)
     */
    private $image_name;

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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

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
     * @var string
     *
     * @ORM\Column(name="old_path_org", type="text", length=500, nullable=true)
     */
    private $old_path_org;

    /**
     * @var string
     *
     * @ORM\Column(name="aws", type="smallint", length=4, options={"default" = 0})
     */
    private $aws;

    /**
     * @var string
     *
     * @ORM\Column(name="local", type="smallint", length=4, options={"default" = 1})
     */
    private $local = 1;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return AdImage
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
     * Set hash
     *
     * @param string $hash
     * @return AdImage
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
     * Set ord
     *
     * @param integer $ord
     * @return AdImage
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord
     *
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set video
     *
     * @param string $video
     * @return AdImage
     */
    public function setVideo($video)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video
     *
     * @return string
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set video_url
     *
     * @param string $videoUrl
     * @return AdImage
     */
    public function setVideoUrl($videoUrl)
    {
        $this->video_url = $videoUrl;

        return $this;
    }

    /**
     * Get video_url
     *
     * @return string
     */
    public function getVideoUrl()
    {
        return $this->video_url;
    }

    /**
     * Set image_name
     *
     * @param string $image_name
     * @return AdImage
     */
    public function setImageName($image_name)
    {
        $this->image_name = $image_name;

        return $this;
    }

    /**
     * Get image_name
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->image_name;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return AdImage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
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
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return AdImage
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set session_id
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
     * Get session_id
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
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

    /**
     * Set old_path_org
     *
     * @param string $old_path_org
     * @return AdImage
     */
    public function setOldPathOrg($old_path_org)
    {
        $this->old_path_org = $old_path_org;

        return $this;
    }

    /**
     * Get old_path_org
     *
     * @return string
     */
    public function getOldPathOrg()
    {
        return $this->old_path_org;
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

    public function getAws()
    {
        return $this->aws;
    }

    public function setAws($aws)
    {
        $this->aws = $aws;
        return $this;
    }

    /**
     * get local
     *
     * @return string
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * set local flag
     *
     * @param integer $local
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdImage
     */
    public function setLocal($local)
    {
        $this->local = $local;
        return $this;
    }
}
