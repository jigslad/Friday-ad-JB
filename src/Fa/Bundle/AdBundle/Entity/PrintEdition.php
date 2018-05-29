<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Fa\Bundle\AdBundle\Entity\PrintEdition
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="print_edition")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PrintEditionRepository")
 * @Gedmo\TranslationEntity(class="Fa\Bundle\AdBundle\Entity\PrintEditionTranslation")
 * @UniqueEntity(fields="name", message="This print edition name is already exist in our system.")
 * @ORM\HasLifecycleCallbacks
 */
class PrintEdition
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
     * @Assert\NotBlank(message="Name is required.")
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20)
     * @Assert\NotBlank(message="Code is required.")
     */
    private $code;

    /**
     * @var integer
     *
     * @Assert\NotBlank(message="Deadline day is required.")
     * @ORM\Column(name="deadline_day_of_week", type="integer", length=3)
     */
    private $deadline_day_of_week;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Deadline time is required.")
     * @ORM\Column(name="deadline_time_of_day", type="string", length=255)
     */
    private $deadline_time_of_day;

    /**
     * @var integer
     *
     * @Assert\NotBlank(message="Insert date day is required.")
     * @ORM\Column(name="insert_date_day_of_week", type="integer", length=3)
     */
    private $insert_date_day_of_week;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Insert date time is required.")
     * @ORM\Column(name="insert_date_time_of_day", type="string", length=255)
     */
    private $insert_date_time_of_day;

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
     * @ORM\OneToMany(targetEntity="Fa\Bundle\AdBundle\Entity\PrintEditionTranslation", mappedBy="object", cascade={"persist","remove"})
     */
    private $translations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\AdBundle\Entity\PrintEditionRule", mappedBy="print_edition", cascade={"persist","remove"})
     */
    private $print_edition_rules;

    /**
     * @var boolean
     *
     * @Assert\NotBlank(message="Status is required.")
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

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
        $this->print_edition_rules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations        = new \Doctrine\Common\Collections\ArrayCollection();
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
     *
     * @return PrintEdition
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
     * Set edition code
     *
     * @param string $editionCode
     *
     * @return PrintEdition
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get edition code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return PrintEdition
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
     * @return PrintEdition
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
     * Add translation
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintEditionTranslation $translation
     *
     * @return PrintEdition
     */
    public function addTranslation(\Fa\Bundle\AdBundle\Entity\PrintEditionTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintEditionTranslation $translation
     */
    public function removeTranslation(\Fa\Bundle\AdBundle\Entity\PrintEditionTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add print edition rules
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintEditionRule $printEditionRule
     * @return User
     */
    public function addPrintEditionRule(\Fa\Bundle\AdBundle\Entity\PrintEditionRule $printEditionRule)
    {
        $this->print_edition_rules[] = $printEditionRule;

        return $this;
    }

    /**
     * Remove print edition rules
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintEditionRule $printEditionRule
     */
    public function removePrintEditionRule(\Fa\Bundle\AdBundle\Entity\PrintEditionRule $printEditionRule)
    {
        $this->print_edition_rules->removeElement($printEditionRule);
    }

    /**
     * Get print edition rules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrintEditionRules()
    {
        return $this->print_edition_rules;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return PrintEdition
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
     * Set deadline day of week
     *
     * @param string $deadlineDayOfWeek
     *
     * @return PrintEdition
     */
    public function setDeadlineDayOfWeek($deadlineDayOfWeek)
    {
        $this->deadline_day_of_week = $deadlineDayOfWeek;

        return $this;
    }

    /**
     * Get deadline day of week
     *
     * @return string
     */
    public function getDeadlineDayOfWeek()
    {
        return $this->deadline_day_of_week;
    }

    /**
     * Set deadline time of day
     *
     * @param string $deadlineTimeOfDay
     *
     * @return PrintEdition
     */
    public function setDeadlineTimeOfDay($deadlineTimeOfDay)
    {
        $this->deadline_time_of_day = $deadlineTimeOfDay;

        return $this;
    }

    /**
     * Get deadline time of day
     *
     * @return string
     */
    public function getDeadlineTimeOfDay()
    {
        return $this->deadline_time_of_day;
    }

    /**
     * Set insert date day of week
     *
     * @param string $insertDateDayOfWeek
     *
     * @return PrintEdition
     */
    public function setInsertDateDayOfWeek($insertDateDayOfWeek)
    {
        $this->insert_date_day_of_week = $insertDateDayOfWeek;

        return $this;
    }

    /**
     * Get insert date day of week
     *
     * @return string
     */
    public function getInsertDateDayOfWeek()
    {
        return $this->insert_date_day_of_week;
    }

    /**
     * Set insert date time of day
     *
     * @param string $insertDateTimeOfDay
     *
     * @return PrintEdition
     */
    public function setInsertDateTimeOfDay($insertDateTimeOfDay)
    {
        $this->insert_date_time_of_day = $insertDateTimeOfDay;

        return $this;
    }

    /**
     * Get deadline time of day
     *
     * @return string
     */
    public function getInsertDateTimeOfDay()
    {
        return $this->insert_date_time_of_day;
    }
}
