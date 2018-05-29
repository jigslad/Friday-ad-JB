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
 * @ORM\Table(name="ti_motors_redirects", indexes={@Index(name="fa_redirect_nval", columns={"nval"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\TiMotorsRedirectsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TiMotorsRedirects
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
     * @ORM\Column(name="nval", type="string", length=500, nullable=true)
     */
    private $nval;

    /**
     * @var string
     *
     * @ORM\Column(name="field_name", type="string", length=500, nullable=true)
     */
    private $field_name;

    /**
     * @var string
     *
     * @ORM\Column(name="mapped_id", type="integer", length=4)
     */
    private $mapped_id;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_cat_id", type="integer", length=4)
     */
    private $parent_cat_id;

    /**
     * @var string
     *
     * @ORM\Column(name="parent", type="string", length=50, nullable=true)
     */
    private $parent;

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
     * Set nval.
     *
     * @param string $nval
     *
     * @return Redirects
     */
    public function setNval($nval)
    {
        $this->nval = $nval;

        return $this;
    }

    /**
     * Get nval.
     *
     * @return string
     */
    public function getNval()
    {
        return $this->nval;
    }

    /**
     * Set field_name.
     *
     * @param string $field_name
     *
     * @return Redirects
     */
    public function setFieldName($field_name)
    {
        $this->field_name = $field_name;

        return $this;
    }

    /**
     * Get field_name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->field_name;
    }

    /**
     * Set parent.
     *
     * @param string $parent
     *
     * @return Redirects
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set mapped_id.
     *
     * @param string $mapped_id
     *
     * @return Redirects
     */
    public function setMappedId($mapped_id)
    {
        $this->mapped_id = $mapped_id;

        return $this;
    }

    /**
     * Get mapped_id
     *
     * @return string
     */
    public function getMappedId()
    {
        return $this->mapped_id;
    }

    /**
     * Set parent_cat_id.
     *
     * @param string $parent_cat_id
     *
     * @return Redirects
     */
    public function setParentCatId($parent_cat_id)
    {
        $this->parent_cat_id = $parent_cat_id;

        return $this;
    }

    /**
     * Get parent_cat_id
     *
     * @return string
     */
    public function getParentCatId()
    {
        return $this->parent_cat_id;
    }
}
