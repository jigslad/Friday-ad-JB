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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store nimber task ids.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="ad_nimber")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdNimberRepository")
 */
class AdNimber
{
    /**
     * Id.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Ad.
     *
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

    /**
     * Buyer user id.
     *
     * @var integer
     *
     * @ORM\Column(name="buyer_user_id", type="integer", nullable=true)
     */
    private $buyer_user_id;


    /**
     * Emnail.
     *
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=false)
     */
    private $email;

    /**
     * Phone.
     *
     * @var string
     *
     * @ORM\Column(name="phone", type="string", nullable=false)
     */
    private $phone;

    /**
     * First name.
     *
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", nullable=false)
     */
    private $first_name;

    /**
     * Last name.
     *
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", nullable=false)
     */
    private $last_name;

    /**
     * Nimber task id.
     *
     * @var string
     *
     * @ORM\Column(name="nimber_task_id", type="string", nullable=false)
     */
    private $nimber_task_id;

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
     * Set ad.
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return AdNimber
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

    /**
     * Set buyer user id.
     *
     * @param integer $buyerUser
     *
     * @return AdNimber
     */
    public function setBuyerUserId($buyerUserId = null)
    {
        $this->buyer_user_id = $buyerUserId;

        return $this;
    }

    /**
     * Get buyer user id.
     *
     * @return integer
     */
    public function getBuyerUserId()
    {
        return $this->buyer_user_id;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return AdNimber
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     * @return AdNimber
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set first_name.
     *
     * @param string $first_name
     * @return AdNimber
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Get first_name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name.
     *
     * @param string $last_name
     * @return AdNimber
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Get last_name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set nimber_task_id.
     *
     * @param string $nimber_task_id
     * @return AdNimber
     */
    public function setNimberTaskId($nimber_task_id)
    {
        $this->nimber_task_id = $nimber_task_id;

        return $this;
    }

    /**
     * Get nimber_task_id.
     *
     * @return string
     */
    public function getNimberTaskId()
    {
        return $this->nimber_task_id;
    }
}
