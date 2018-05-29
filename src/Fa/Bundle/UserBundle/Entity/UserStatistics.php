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

/**
 * This table is used to store statistics of user such as ad counter etc...
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_statistics", indexes={@ORM\Index(name="fa_user_user_statistics_total_ad_index", columns={"total_ad"}), @ORM\Index(name="fa_user_user_statistics_total_active_ad_index", columns={"total_active_ad"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserStatisticsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserStatistics
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
     * @ORM\Column(name="total_ad", type="integer", nullable=true, options={"default" = 0})
     */
    private $total_ad;

    /**
     * @var string
     *
     * @ORM\Column(name="total_active_ad", type="integer", nullable=true, options={"default" = 0})
     */
    private $total_active_ad;

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
     * @ORM\OneToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true, onDelete="CASCADE")
     * })
     */
    private $user;


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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set total ad
     *
     * @param integer $total_ad
     * @return UserStatistics
     */
    public function setTotalAd($total_ad)
    {
        $this->total_ad = $total_ad;

        return $this;
    }

    /**
     * Get total ad
     *
     * @return integer
     */
    public function getTotalAd()
    {
        return $this->total_ad;
    }

    /**
     * Set total active ad
     *
     * @param integer $total_active_ad
     * @return UserStatistics
     */
    public function setTotalActiveAd($total_active_ad)
    {
        $this->total_active_ad = $total_active_ad;

        return $this;
    }

    /**
     * Get total active ad
     *
     * @return integer
     */
    public function getTotalActiveAd()
    {
        return $this->total_active_ad;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserSite
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
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return UserStatistics
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param integer $updatedAt
     *
     * @return UserStatistics
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
