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
use Doctrine\ORM\Mapping\Index as Index;

/**
 * This table is used to store ad image information.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="redirects", indexes={@Index(name="fa_redirect_old", columns={"old"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\RedirectsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Redirects
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
     * @ORM\Column(name="old", type="string", length=500, nullable=true)
     */
    private $old;

    /**
     * @var string
     *
     * @ORM\Column(name="new", type="string", length=500, nullable=true)
     */
    private $new;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_location", type="boolean", nullable=true)
     */
    private $is_location;


    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
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
     * Set old.
     *
     * @param string $old
     *
     * @return Redirects
     */
    public function setOld($old)
    {
        $this->old = $old;

        return $this;
    }

    /**
     * Get old.
     *
     * @return string
     */
    public function getOld()
    {
        return $this->old;
    }

    /**
     * Set new.
     *
     * @param string $name
     *
     * @return Redirects
     */
    public function setNew($new)
    {
        $this->new = $new;

        return $this;
    }

    /**
     * Get new.
     *
     * @return string
     */
    public function getNew()
    {
        return $this->new;
    }

    /**
     * Set new.
     *
     * @param boolean $is_location
     *
     * @return Redirects
     */
    public function setIsLocation($is_location)
    {
        $this->is_location = $is_location;

        return $this;
    }

    /**
     * Get new.
     *
     * @return boolean
     */
    public function getIsLocation()
    {
        return $this->is_location;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return Shortlist
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
}
