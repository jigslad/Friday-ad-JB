<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store upsell information.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="boosted_ad")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\BoostedAdRepository")
 * @ORM\HasLifecycleCallbacks
 */
class BoostedAd
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
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
    */
    private $ad;

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
     *
     * @var integer @ORM\Column(name="boosted_at", type="integer", length=10, nullable=true, options={"default" = 0})
    */
    
    private $boosted_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="smallint", options={"default" = 1})
     */
    private $status;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return BoostedAd
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
     * set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return BoostedAd
     */
    public function setAd($ad)
    {
        $this->ad = $ad;
        return $this;
    }


    /**
     * get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return BoostedAd
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
     * Set boosted_at
     *
     * @param integer $boosted_at
     * @return BoostedAd
     */
    public function setBoostedAt($boosted_at)
    {
        $this->boosted_at = $boosted_at;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return integer
     */
    public function getBoostedAt()
    {
        return $this->boosted_at;
    }

    /**
     * Set created at value.
     *
     * @ORM\PrePersist()
     */
    public function setBoostedAtValue()
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
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return BoostedAd
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
     * Set status
     *
     * @param integer $status
     *
     * @return BoostedAd
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}
