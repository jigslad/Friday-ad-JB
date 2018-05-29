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
 * This table is used to store mapping information of role, resource & permission.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="role_resource_permission")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\RoleResourcePermissionRepository")
 */
class RoleResourcePermission
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
     * @var \Fa\Bundle\UserBundle\Entity\Role
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Role", inversedBy="role_resource_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $role;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Permission
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Permission", inversedBy="role_resource_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="permission_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $permission;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Resource
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Resource", inversedBy="role_resource_permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="resource_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $resource;



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
     * Set role
     *
     * @param \Fa\Bundle\UserBundle\Entity\Role $role
     * @return RoleResourcePermission
     */
    public function setRole(\Fa\Bundle\UserBundle\Entity\Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Fa\Bundle\UserBundle\Entity\Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set permission
     *
     * @param \Fa\Bundle\UserBundle\Entity\Permission $permission
     * @return RoleResourcePermission
     */
    public function setPermission(\Fa\Bundle\UserBundle\Entity\Permission $permission = null)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission
     *
     * @return \Fa\Bundle\UserBundle\Entity\Permission
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Set resource
     *
     * @param \Fa\Bundle\UserBundle\Entity\Resource $resource
     * @return RoleResourcePermission
     */
    public function setResource(\Fa\Bundle\UserBundle\Entity\Resource $resource = null)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get resource
     *
     * @return \Fa\Bundle\UserBundle\Entity\Resource
     */
    public function getResource()
    {
        return $this->resource;
    }
}
