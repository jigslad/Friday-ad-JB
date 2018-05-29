<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdBundle\Entity\PrintEditionRule
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="print_edition_rule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PrintEditionRuleRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PrintEditionRule
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
     * @var \Fa\Bundle\AdBundle\Entity\PrintEdition
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\PrintEdition", inversedBy="print_edition_rules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="print_edition_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $print_edition;

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
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
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
     * Set print edition
     *
     * @param object $printEdition
     * @return PrintEditionRule
     */
    public function setPrintEdition(\Fa\Bundle\AdBundle\Entity\PrintEdition $printEdition)
    {
        $this->print_edition = $printEdition;

        return $this;
    }

    /**
     * Get print edition
     *
     * @return string
     */
    public function getPrintEdition()
    {
        return $this->print_edition;
    }

    /**
     * Set location group
     *
     * @param object $locationGroup
     * @return PrintEditionRule
     */
    public function setLocationGroup(\Fa\Bundle\EntityBundle\Entity\LocationGroup $locationGroup)
    {
        $this->location_group = $locationGroup;

        return $this;
    }

    /**
     * Get print edition
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
