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
 * This table is used to store Competition information.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="competition")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\CompetitionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Competition
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
     * @var \Fa\Bundle\UserBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="competition_type_id", referencedColumnName="id")
     * })
     */
    private $competition_type;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="cart_code", type="string", length=40)
     */
    private $cart_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="birth_date", type="string", length=20, nullable=true)
     */
    private $birth_date;

    /**
     * @var string
     *
     * @ORM\Column(name="interest", type="string", nullable=true)
     */
    private $interest;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

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
     * Set created at value.
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return PackageRule
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
     * Set competition_type
     *
     * @param \Fa\Bundle\UserBundle\Entity\Entity $competition_type
     * @return PackageRule
     */
    public function setCompetitionType(\Fa\Bundle\EntityBundle\Entity\Entity $competition_type = null)
    {
        $this->competition_type = $competition_type;

        return $this;
    }

    /**
     * Get competition_type
     */
    public function getCompetitionType()
    {
        return $this->competition_type;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return Competition
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
     * Set cartCode
     *
     * @param string $cartCode
     *
     * @return Competition
     */
    public function setCartCode($cartCode)
    {
        $this->cart_code = $cartCode;

        return $this;
    }

    /**
     * Get cartCode
     *
     * @return string
     */
    public function getCartCode()
    {
        return $this->cart_code;
    }

    /**
     * Set birth_date.
     *
     * @param string $birth_date
     * @return Competition
     */
    public function setBirthDate($birth_date)
    {
        $this->birth_date = $birth_date;

        return $this;
    }

    /**
     * Get birth_date.
     *
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * Set interest.
     *
     * @param string $interest
     * @return Competition
     */
    public function setInterest($interest)
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * Get interest.
     *
     * @return string
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return EmailTemplate
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
}
