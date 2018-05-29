<?php

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\UserBundle\Entity\UserSiteImage
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="user_site_image")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserSiteImageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserSiteImage
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
     * @var \Fa\Bundle\UserBundle\Entity\UserSite
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\UserSite")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_site_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $user_site;

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
     * Set id
     *
     * @param integer $id
     *
     * @return Ad
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set user site
     *
     * @param \Fa\Bundle\UserBundle\Entity\UserSite $userSite
     * @return UserSite
     */
    public function setUserSite(\Fa\Bundle\UserBundle\Entity\UserSite $userSite = null)
    {
        $this->user_site = $userSite;

        return $this;
    }

    /**
     * Get user site
     *
     * @return \Fa\Bundle\UserBundle\Entity\UserSite
     */
    public function getUserSite()
    {
        return $this->user_site;
    }
}
