<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdBundle\Entity\PrintDeadlineRule
 *
 * This table is used to store print deadline information.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="print_deadline_rule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PrintDeadlineRuleRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PrintDeadlineRule
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
     * @var \Fa\Bundle\AdBundle\Entity\PrintDeadline
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\PrintDeadline", inversedBy="print_deadline_rules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="print_deadline_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $print_deadline;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\LocationGroup
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\LocationGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_group_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_group;

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
     * Constructor
     */
    public function __construct()
    {
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
     * Set print deadline
     *
     * @param object $printDeadline
     * @return PrintDeadlineRule
     */
    public function setPrintDeadline(\Fa\Bundle\AdBundle\Entity\PrintDeadline $printDeadline)
    {
        $this->print_deadline = $printDeadline;

        return $this;
    }

    /**
     * Get print deadline
     *
     * @return string
     */
    public function getPrintDeadline()
    {
        return $this->print_deadline;
    }

    /**
     * Set location group
     *
     * @param object $locationGroup
     * @return PrintDeadlineRule
     */
    public function setLocationGroup(\Fa\Bundle\EntityBundle\Entity\LocationGroup $locationGroup)
    {
        $this->location_group = $locationGroup;

        return $this;
    }

    /**
     * Get location group
     *
     * @return string
     */
    public function getLocationGroup()
    {
        return $this->location_group;
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
     * toString
     */
    public function __toString()
    {
        return '';
    }
}
