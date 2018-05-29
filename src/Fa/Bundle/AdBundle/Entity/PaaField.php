<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\AdBundle\Entity\PaaField
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="paa_field")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PaaFieldRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaaField
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
     * @ORM\Column(name="field", type="string", length=255)
     */
    private $field;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="field_type", type="string", length=255)
     */
    private $field_type;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_dimension_id", type="integer", length=10, nullable=true)
     */
    private $category_dimension_id;

    /**
     * @var string
     *
     * @ORM\Column(name="is_index", type="integer", nullable=true)
     */
    private $is_index;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
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
     * Set field
     *
     * @param string $field
     * @return PaaField
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return PaaField
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set field type
     *
     * @param string $fieldType
     * @return PaaField
     */
    public function setFieldType($fieldType)
    {
        $this->field_type = $fieldType;

        return $this;
    }

    /**
     * Get field type
     *
     * @return string
     */
    public function getFieldType()
    {
        return $this->field_type;
    }

    /**
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return Ad
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
     * Set category dimension id
     *
     * @param string $categoryDimensionId
     * @return PaaField
     */
    public function setCategoryDimensionId($categoryDimensionId)
    {
        $this->category_dimension_id = $categoryDimensionId;

        return $this;
    }

    /**
     * Get category dimension id
     *
     * @return integer
     */
    public function getCategoryDimensionId()
    {
        return $this->category_dimension_id;
    }

    /**
     * Set is index
     *
     * @param boolean $isIndex
     * @return PaaField
     */
    public function setIsIndex($isIndex)
    {
        $this->is_index = $isIndex;

        return $this;
    }

    /**
     * Get is index
     *
     * @return boolean
     */
    public function getIsIndex()
    {
        return $this->is_index;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return PaaField
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
     * Get payment
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
    }
}
