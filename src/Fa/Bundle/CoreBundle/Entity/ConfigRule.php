<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store core configuration parameters of the system.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="config_rule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\CoreBundle\Repository\ConfigRuleRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\CoreBundle\Listener\ConfigRuleListener" })
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class ConfigRule
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
     * @Assert\NotBlank(message="Value is required.")
     * @ORM\Column(name="value", type="string", length=2000)
     * @Gedmo\Versioned
     */
    private $value;

    /**
     * @var \Fa\Bundle\CoreBundle\Entity\Config
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\CoreBundle\Entity\Config")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="config_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $config;

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
     * @var \Fa\Bundle\EntityBundle\Entity\LocationGroup
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\LocationGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_group_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\Versioned
     */
    private $location_group;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Gedmo\Versioned
     */
    private $status;

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
     * @var integer
     *
     * @ORM\Column(name="period_from", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $period_from;

    /**
     * @var integer
     *
     * @ORM\Column(name="period_to", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $period_to;

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
     * Set value.
     *
     * @param string $value
     *
     * @return CoreConfigRule
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set category.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     *
     * @return CoreConfigRule
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set location group.
     *
     * @param object $locationGroup
     *
     * @return PrintEditionRule
     */
    public function setLocationGroup(\Fa\Bundle\EntityBundle\Entity\LocationGroup $locationGroup)
    {
        $this->location_group = $locationGroup;

        return $this;
    }

    /**
     * Get print edition.
     *
     * @return string
     */
    public function getLocationGroup()
    {
        return $this->location_group;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return AdImage
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
     * Set created_at.
     *
     * @param integer $createdAt
     *
     * @return Ad
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
     * @return Ad
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
     * Set config.
     *
     * @param \Fa\Bundle\CoreBundle\Entity\Config $config
     *
     * @return ConfigRule
     */
    public function setConfig(\Fa\Bundle\CoreBundle\Entity\Config $config = null)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config.
     *
     * @return \Fa\Bundle\CoreBundle\Entity\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set period_from.
     *
     * @param integer $periodFrom
     *
     * @return ConfigRule
     */
    public function setPeriodFrom($periodFrom)
    {
        $this->period_from = $periodFrom;

        return $this;
    }

    /**
     * Get period_from.
     *
     * @return integer
     */
    public function getPeriodFrom()
    {
        return $this->period_from;
    }

    /**
     * Set period_to.
     *
     * @param integer $periodTo
     *
     * @return ConfigRule
     */
    public function setPeriodTo($periodTo)
    {
        $this->period_to = $periodTo;

        return $this;
    }

    /**
     * Get period_to.
     *
     * @return integer
     */
    public function getPeriodTo()
    {
        return $this->period_to;
    }
}
