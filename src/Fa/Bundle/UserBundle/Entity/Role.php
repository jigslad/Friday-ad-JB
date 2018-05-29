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
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\Role\Role as SymRole;

/**
 * This method is used to store role information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="role")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\RoleRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\UserBundle\Listener\RoleListener" })
 */
class Role extends SymRole // implements RoleInterface
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

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=2)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="site_ids", type="string", length=50, nullable=true)
     */
    private $site_ids;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\RoleResourcePermission", mappedBy="role")
     */
    private $role_resource_permissions;

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
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->getName();
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
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * Set type
     *
     * @param string $type
     * @return Role
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set site_ids
     *
     * @param string $siteIds
     * @return Role
     */
    public function setSiteIds($siteIds)
    {
        $this->site_ids = $siteIds;

        return $this;
    }

    /**
     * Get site_ids
     *
     * @return string
     */
    public function getSiteIds()
    {
        return $this->site_ids;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Role
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
     * @return Role
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
     * @return Role
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
     * Get payment
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return $this->name;
    }
}
