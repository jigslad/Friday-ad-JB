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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store information about location radius.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="location_radius")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\LocationRadiusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class LocationRadius
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
     * @var integer
     *
     * @ORM\Column(name="default_radius", type="integer", length=10, nullable=true)
    */
    private $defaultRadius;

    /**
     * @var integer
     *
     * @ORM\Column(name="extended_radius", type="integer", length=10, nullable=true)
    */
    private $extendedRadius;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10, nullable=true)
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
     * @Assert\NotBlank(message="Please select status.")
     */
    private $status;


    /**
     * Constructor.
     */
    public function __construct()
    {
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
     * Set status.
     *
     * @param boolean $status
     *
     * @return StaticPage
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
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return LocationRadius
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set created_at.
     *
     * @param integer $createdAt
     *
     * @return LocationRadius
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
     * @return LocationRadius
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
     * Set default Radius
     *
     * @param integer $defaultRadius
     *
     * @return LocationRadius
     */
    public function setDefaultRadius($defaultRadius)
    {
        $this->defaultRadius = $defaultRadius;

        return $this;
    }

    /**
     * Get default Radius
     *
     * @return integer
     */
    public function getDefaultRadius()
    {
        return $this->defaultRadius;
    }

    /**
     * Set extended Radius.
     *
     * @param integer $extendedRadius
     *
     * @return LocationRadius
     */
    public function setExtendedRadius($extendedRadius)
    {
        $this->extendedRadius = $extendedRadius;

        return $this;
    }

    /**
     * Get extended Radius.
     *
     * @return integer
     */
    public function getExtendedRadius()
    {
        return $this->extendedRadius;
    }
}
