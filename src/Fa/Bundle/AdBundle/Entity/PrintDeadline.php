<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdBundle\Entity\PrintDeadline
 *
 * This table is used to store print deadline deadline information.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="print_deadline")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PrintDeadlineRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PrintDeadline
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
     * @var integer
     *
     * @Assert\NotBlank(message="Day is required.")
     * @ORM\Column(name="day_of_week", type="integer", length=3)
     */
    private $day_of_week;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Time is required.")
     * @ORM\Column(name="time_of_day", type="string", length=255)
     */
    private $time_of_day;

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
     * @ORM\OneToMany(targetEntity="Fa\Bundle\AdBundle\Entity\PrintDeadlineRule", mappedBy="print_deadline", cascade={"persist","remove"})
     */
    private $print_deadline_rules;

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
     * Constructor
     */
    public function __construct()
    {
        $this->print_deadline_rules = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set day of week
     *
     * @param string $dayOfWeek
     *
     * @return PrintDeadline
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->day_of_week = $dayOfWeek;

        return $this;
    }

    /**
     * Get day of week
     *
     * @return string
     */
    public function getDayOfWeek()
    {
        return $this->day_of_week;
    }

    /**
     * Set time of day
     *
     * @param string $timeOfDay
     *
     * @return PrintDeadline
     */
    public function setTimeOfDay($timeOfDay)
    {
        $this->time_of_day = $timeOfDay;

        return $this;
    }

    /**
     * Get time of day
     *
     * @return string
     */
    public function getTimeOfDay()
    {
        return $this->time_of_day;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return PrintDeadline
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param integer $updatedAt
     *
     * @return PrintDeadline
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Add print deadline rules
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintDeadlineRule $printDeadlineRule
     * @return User
     */
    public function addPrintDeadlineRule(\Fa\Bundle\AdBundle\Entity\PrintDeadlineRule $printDeadlineRule)
    {
        $this->print_deadline_rules[] = $printDeadlineRule;

        return $this;
    }

    /**
     * Remove print deadline rules
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintDeadlineRule $printDeadlineRule
     */
    public function removePrintDeadlineRule(\Fa\Bundle\AdBundle\Entity\PrintDeadlineRule $printDeadlineRule)
    {
        $this->print_deadline_rules->removeElement($printDeadlineRule);
    }

    /**
     * Get print deadline rules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrintDeadlineRules()
    {
        return $this->print_deadline_rules;
    }
}
