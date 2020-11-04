<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\PaaFieldRule
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="paa_field_rule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\AdBundle\Listener\PaaFieldRuleListener" })
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class PaaFieldRule
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
     * @var \Fa\Bundle\AdBundle\Entity\PaaField
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\PaaField")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="paa_field_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $paa_field;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Gedmo\Versioned
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_required;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_recommended", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_recommended;

    /**
     * @var string
     *
     * @ORM\Column(name="help_text", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $help_text;

    /**
     * @var string
     *
     * @ORM\Column(name="error_text", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $error_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="ord", type="smallint", length=4)
     * @Gedmo\Versioned
     */
    private $ord;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $default_value;

    /**
     * @var float
     *
     * @ORM\Column(name="min_value", type="float", precision=15, scale=2, nullable=true)
     * @Gedmo\Versioned
     */
    private $min_value;

    /**
     * @var float
     *
     * @ORM\Column(name="max_value", type="float", precision=15, scale=2, nullable=true)
     * @Gedmo\Versioned
     */
    private $max_value;

    /**
     * @var string
     *
     * @ORM\Column(name="min_max_type", type="string", length=20, nullable=true, options={"default" = "LENGTH"})
     */
    private $min_max_type;

    /**
     * @var string
     *
     * @ORM\Column(name="field_type", type="string", length=255, nullable=true)
     */
    private $field_type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_toggle", type="boolean", nullable=true, options={"default" = 1})
     */
    private $is_toggle;

    /**
     * @var integer
     *
     * @ORM\Column(name="step", type="smallint", length=4, nullable=true)
     * @Gedmo\Versioned
     */
    private $step;

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
     * @var string
     *
     * @ORM\Column(name="placeholder_text", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $placeholder_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_added", type="smallint", length=4, nullable=true)
    */
    private $is_added;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hide_field", type="boolean", nullable=true, options={"default" = 1})
     * @Gedmo\Versioned
     */
    private $hide_field;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
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
     * Set label
     *
     * @param string $label
     *
     * @return PaaFieldRule
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
     * Set status
     *
     * @param boolean $status
     * @return PaaFieldRule
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
     * Set is required
     *
     * @param boolean $isRequired
     * @return PaaFieldRule
     */
    public function setIsRequired($isRequired)
    {
        $this->is_required = $isRequired;

        return $this;
    }

    /**
     * Get is required
     *
     * @return boolean
     */
    public function getIsRequired()
    {
        return $this->is_required;
    }

    /**
     * Set is recommended
     *
     * @param boolean $isRecommended
     * @return PaaFieldRule
     */
    public function setIsRecommended($isRecommended)
    {
        $this->is_recommended = $isRecommended;

        return $this;
    }

    /**
     * Get is recommended
     *
     * @return boolean
     */
    public function getIsRecommended()
    {
        return $this->is_recommended;
    }

    /**
     * Set help text
     *
     * @param string $helpText
     *
     * @return PaaFieldRule
     */
    public function setHelpText($helpText)
    {
        $this->help_text = $helpText;

        return $this;
    }

    /**
     * Get help text
     *
     * @return string
     */
    public function getHelpText()
    {
        return $this->help_text;
    }

    /**
     * Set error text
     *
     * @param string $errorText
     *
     * @return PaaFieldRule
     */
    public function setErrorText($errorText)
    {
        $this->error_text = $errorText;

        return $this;
    }

    /**
     * Get error text
     *
     * @return string
     */
    public function getErrorText()
    {
        return $this->error_text;
    }

    /**
     * Set ord
     *
     * @param integer $ord
     * @return PaaFieldRule
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord
     *
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set default value
     *
     * @param string $defaultValue
     * @return PaaFieldRule
     */
    public function setDefaultValue($defaultValue)
    {
        $this->default_value = $defaultValue;

        return $this;
    }

    /**
     * Get default value
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->default_value;
    }

    /**
     * Set minimum value
     *
     * @param integer $minValue
     * @return PaaFieldRule
     */
    public function setMinValue($minValue)
    {
        $this->min_value = $minValue;

        return $this;
    }

    /**
     * Get minimum value
     *
     * @return integer
     */
    public function getMinValue()
    {
        return $this->min_value;
    }

    /**
     * Set maximum value
     *
     * @param integer $maxValue
     * @return PaaFieldRule
     */
    public function setMaxValue($maxValue)
    {
        $this->max_value = $maxValue;

        return $this;
    }

    /**
     * Get maximum value
     *
     * @return integer
     */
    public function getMaxValue()
    {
        return $this->max_value;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return PaaFieldRule
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
     * @return PaaFieldRule
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
     * Set paa_field
     *
     * @param \Fa\Bundle\AdBundle\Entity\PaaField $paaField
     * @return PaaFieldRule
     */
    public function setPaaField(\Fa\Bundle\AdBundle\Entity\PaaField $paaField = null)
    {
        $this->paa_field = $paaField;

        return $this;
    }

    /**
     * Get paa_field
     *
     * @return \Fa\Bundle\AdBundle\Entity\PaaField
     */
    public function getPaaField()
    {
        return $this->paa_field;
    }

    /**
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return PaaFieldRule
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
     * Get min and max type
     *
     * @return string
     */
    public function getMinMaxType()
    {
        return $this->min_max_type;
    }

    /**
     * Set type of min and max
     *
     * @param string $maxMaxType
     * @return PaaFieldRule
     */
    public function setMinMaxType($minMaxType)
    {
        $this->min_max_type = $minMaxType;

        return $this;
    }

    /**
     * Set field type
     *
     * @param string $fieldType
     * @return PaaFieldRule
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
     * Set is google
     *
     * @param boolean $isToggle
     * @return PaaFieldRule
     */
    public function setIsToggle($isToggle)
    {
        $this->is_toggle = $isToggle;

        return $this;
    }

    /**
     * Get is toggle
     *
     * @return boolean
     */
    public function getIsToggle()
    {
        return $this->is_toggle;
    }

    /**
     * Set step
     *
     * @param integer $step
     * @return PaaFieldRule
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get step
     *
     * @return integer
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Set placeholder_text.
     *
     * @param string $placeholder_text
     * @return PaaFieldRule
     */
    public function setPlaceholderText($placeholder_text)
    {
        $this->placeholder_text = $placeholder_text;

        return $this;
    }

    /**
     * Get placeholder_text.
     *
     * @return string
     */
    public function getPlaceholderText()
    {
        return $this->placeholder_text;
    }

    /**
     * Set is_added
     *
     * @param integer $isAdded
     * @return PaaFieldRule
     */
    public function setIsAdded($isAdded)
    {
        $this->is_added = $isAdded;

        return $this;
    }

    /**
     * Get step
     *
     * @return integer
     */
    public function getIsAdded()
    {
        return $this->is_added;
    }

    /**
     * Set is hide field
     *
     * @param tinyint $hideField
     * @return PaaFieldRule
     */
    public function setHideField($hideField)
    {
        $this->hide_field = $hideField;
        return $this;
    }

    /**
     * Get is hide field
     *
     * @return tinyint
     */
    public function getHideField()
    {
        return $this->hide_field;
    }
}
