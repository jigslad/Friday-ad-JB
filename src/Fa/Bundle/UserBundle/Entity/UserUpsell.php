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
 * This table is used to store site information of user.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_upsell")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserUpsellRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserUpsell
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
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * @var \Fa\Bundle\PromotionBundle\Entity\Package
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Package")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $package;

    /**
     * @var \Fa\Bundle\PromotionBundle\Entity\Upsell
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Upsell")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="upsell_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $upsell;

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
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;


    /**
     * Get status.
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
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserUpsell
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get pacakge.
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set pacakge.
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Package $package
     *
     * @return UserUpsell
     */
    public function setPackage(\Fa\Bundle\PromotionBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package.
     *
     * @return \Fa\Bundle\PromotionBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set pacakge.
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Upsell $upsell
     *
     * @return UserUpsell
     */
    public function setUpsell(\Fa\Bundle\PromotionBundle\Entity\Upsell $upsell = null)
    {
        $this->upsell = $upsell;

        return $this;
    }

    /**
     * Get upsell.
     *
     * @return \Fa\Bundle\PromotionBundle\Entity\Upsell
     */
    public function getUpsell()
    {
        return $this->upsell;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return UserUpsell
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
     * Set created_at.
     *
     * @param integer $createdAt
     *
     * @return UserUpsell
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at.
     *
     * @param integer $updatedAt
     *
     * @return UserUpsell
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

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
}
