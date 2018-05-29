<?php

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\AdBundle\Entity\AdImage
 *
 * This table is used to store ad image information.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="user_image", indexes={@ORM\Index(name="fa_ad_image_ad_ref",  columns={"ad_ref"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserImageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserImage
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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $user;

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
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return AdImage
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
}
