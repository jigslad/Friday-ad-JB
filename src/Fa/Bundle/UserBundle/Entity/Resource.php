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
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store system resource.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="resource")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\ResourceRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Tree(type="nested")
 */
class Resource
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
     * @assert\NotBlank(message="Please enter name.")
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=15, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="resource", type="string", length=100, nullable=true, unique=true)
     */
    private $resource;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_group", type="string", length=100, nullable=true)
     */
    private $resource_group;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_menu", type="boolean")
     */
    private $is_menu;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display_in_tree", type="boolean")
     */
    private $display_in_tree;

    /**
     * @var string
     *
     * @ORM\Column(name="icon_class", type="string", length=20, nullable=true)
     */
    private $icon_class;

    /**
     * @var integer
     *
     * @ORM\Column(name="lft", type="integer")
     * @Gedmo\TreeLeft
     */
    private $lft;

    /**
     * @var integer
     *
     * @ORM\Column(name="rgt", type="integer")
     * @Gedmo\TreeRight
     */
    private $rgt;

    /**
     * @var integer
     *
     * @ORM\Column(name="root", type="integer")
     * @Gedmo\TreeRoot
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="lvl", type="integer")
     * @Gedmo\TreeLevel
     */
    private $lvl;

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
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\Resource", mappedBy="parent")
     * @ORM\OrderBy({
     *     "lft"="ASC"
     * })

     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\RoleResourcePermission", mappedBy="resource")
     */
    private $role_resource_permissions;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Resource
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Resource", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\TreeParent
     */
    private $parent;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Permission
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Permission", inversedBy="resource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="permission_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $permission;

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
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Resource
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
     * Set code
     *
     * @param string $code
     * @return Resource
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set resource
     *
     * @param string $resource
     * @return Resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get resource
     *
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set resource_group
     *
     * @param string $resourceGroup
     * @return Resource
     */
    public function setResourceGroup($resourceGroup)
    {
        $this->resource_group = $resourceGroup;

        return $this;
    }

    /**
     * Get resource_group
     *
     * @return string
     */
    public function getResourceGroup()
    {
        return $this->resource_group;
    }

    /**
     * Set is_menu
     *
     * @param boolean $isMenu
     * @return Resource
     */
    public function setIsMenu($isMenu)
    {
        $this->is_menu = $isMenu;

        return $this;
    }

    /**
     * Get is_menu
     *
     * @return boolean
     */
    public function getIsMenu()
    {
        return $this->is_menu;
    }

    /**
     * Set display_in_tree
     *
     * @param boolean $displayInTree
     * @return Resource
     */
    public function setDisplayInTree($displayInTree)
    {
        $this->display_in_tree = $displayInTree;

        return $this;
    }

    /**
     * Get display_in_tree
     *
     * @return boolean
     */
    public function getDisplayInTree()
    {
        return $this->display_in_tree;
    }

    /**
     * Set icon_class
     *
     * @param string $iconClass
     * @return Resource
     */
    public function setIconClass($iconClass)
    {
        $this->icon_class = $iconClass;

        return $this;
    }

    /**
     * Get icon_class
     *
     * @return string
     */
    public function getIconClass()
    {
        return $this->icon_class;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Resource
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Resource
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Resource
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return Resource
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Resource
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
     * @return Resource
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
     * Add children
     *
     * @param \Fa\Bundle\UserBundle\Entity\Resource $children
     * @return Resource
     */
    public function addChild(\Fa\Bundle\UserBundle\Entity\Resource $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Fa\Bundle\UserBundle\Entity\Resource $children
     */
    public function removeChild(\Fa\Bundle\UserBundle\Entity\Resource $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add role_resource_permissions
     *
     * @param \Fa\Bundle\UserBundle\Entity\RoleResourcePermission $roleResourcePermissions
     * @return Resource
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
     * Set parent
     *
     * @param \Fa\Bundle\UserBundle\Entity\Resource $parent
     * @return Resource
     */
    public function setParent(\Fa\Bundle\UserBundle\Entity\Resource $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Fa\Bundle\UserBundle\Entity\Resource
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set permission
     *
     * @param \Fa\Bundle\UserBundle\Entity\Permission $permission
     * @return Resource
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
