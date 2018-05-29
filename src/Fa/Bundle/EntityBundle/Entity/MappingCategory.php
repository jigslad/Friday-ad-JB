<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store various category dimension.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="mapping_category")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\MappingCategoryRepository")
 */
class MappingCategory
{
    /**
     * Id.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * Id.
     *
     * @var integer
     *
     * @ORM\Column(name="new_id", type="integer", nullable=true)
     */
    private $new_id;

    /**
     * Name.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=200), nullable=true
     */
    private $name;

    /**
     * Dimension id.
     *
     * @var string
     *
     * @ORM\Column(name="dimension_id", type="integer", nullable=true)
     */
    private $dimension_id;


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
     * Set id.
     *
     * @param integer $id
     *
     * @return Entity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return integer
     */
    public function getNewId()
    {
        return $this->new_id;
    }

    /**
     * Set id.
     *
     * @param integer $new_id
     *
     * @return Entity
     */
    public function setNewId($new_id)
    {
        $this->new_id = $new_id;

        return $this;
    }

    /**
     * Get dimension_id.
     *
     * @return integer
     */
    public function getDimensionId()
    {
        return $this->dimension_id;
    }

    /**
     * Set id.
     *
     * @param integer $dimension_id
     *
     * @return Entity
     */
    public function setDimensionId($dimension_id)
    {
        $this->dimension_id = $dimension_id;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CategoryDimension
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
