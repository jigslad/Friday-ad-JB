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
 * This table is used to store system permission.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="permission")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\PermissionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Permission
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /** @var integer
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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\RoleResourcePermission", mappedBy="permission")
     */
    private $role_resource_permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\Resource", mappedBy="permission")
     */
    private $resource;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->role_resource_permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->resource = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Permission
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Permission
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Permission
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
     * @return Permission
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
     * Add role_resource_permissions
     *
     * @param \Fa\Bundle\UserBundle\Entity\RoleResourcePermission $roleResourcePermissions
     * @return Permission
     */
    public function addRoleResourcePermission(\Fa\Bundle\UserBundle\Entity\RoleResourcePermission $roleResourcePermissions)
    {
        $this->role_resource_permissions[] = $roleResourcePermissions;

        return $this;
    }

    /**
     * Remove role_resource_permissions
     *
     * @param \Fa\Bundle\UserBundle\Entity\RoleResourcePermission $roleResourcePermissions
     */
    public function removeRoleResourcePermission(\Fa\Bundle\UserBundle\Entity\RoleResourcePermission $roleResourcePermissions)
    {
        $this->role_resource_permissions->removeElement($roleResourcePermissions);
    }

    /**
     * Get role_resource_permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoleResourcePermissions()
    {
        return $this->role_resource_permissions;
    }

    /**
     * Add resource
     *
     * @param \Fa\Bundle\UserBundle\Entity\Resource $resource
     * @return Permission
     */
    public function addResource(\Fa\Bundle\UserBundle\Entity\Resource $resource)
    {
        $this->resource[] = $resource;

        return $this;
    }

    /**
     * Remove resource
     *
     * @param \Fa\Bundle\UserBundle\Entity\Resource $resource
     */
    public function removeResource(\Fa\Bundle\UserBundle\Entity\Resource $resource)
    {
        $this->resource->removeElement($resource);
    }

    /**
     * Get resource
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResource()
    {
        return $this->resource;
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
}
