<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email template schedule.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="email_template_schedule")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EmailBundle\Repository\EmailTemplateScheduleRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EmailTemplateSchedule
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EmailBundle\Entity\EmailTemplate",cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="email_template_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $email_template;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency", type="string", length=1)
     * @assert\NotBlank(message="Please select frequency.", groups={"one_time", "daily", "weekly", "monthly", "all"})
     */
    private $frequency;

    /**
     * @var integer
     *
     * @ORM\Column(name="date", type="integer", nullable=true)
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="time", type="smallint", options={"default" = 0})
     */
    private $time;

    /**
     * @var integer
     *
     * @ORM\Column(name="after_given_time", type="string", length=20, nullable=true)
     * @assert\NotBlank(message="Please enter time.", groups={"after_given_time"})
     */
    private $after_given_time;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_after_given_time_recurring", type="boolean", nullable=true)
     * @assert\NotBlank(message="Please select recurring.", groups={"after_given_time"})
     */
    private $is_after_given_time_recurring;

    /**
     * @var integer
     *
     * @ORM\Column(name="daily_recur_day", type="smallint", nullable=true)
     * @Assert\Regex(pattern="/^[0-9 ]+$/i", message="The value {{ value }} is not a valid integer.", groups={"daily"})
     * @assert\NotBlank(message="Please enter recur day.", groups={"daily"})
     */
    private $daily_recur_day;

    /**
     * @var integer
     *
     * @ORM\Column(name="weekly_recur_day", type="smallint", nullable=true)
     * @Assert\Regex(pattern="/^[0-9 ]+$/i", message="The value {{ value }} is not a valid integer.", groups={"weekly"})
     * @assert\NotBlank(message="Please enter recur day.", groups={"weekly"})
     */
    private $weekly_recur_day;

    /**
     * @var string
     *
     * @ORM\Column(name="weekly_day", type="string", length=20, nullable=true)
     */
    private $weekly_day;

    /**
     * @var string
     *
     * @ORM\Column(name="monthly_day", type="string", length=50, nullable=true)
     */
    private $monthly_day;

    /**
     * @var string
     *
     * @ORM\Column(name="month", type="string", length=20, nullable=true)
     */
    private $month;

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
     * Set frequency.
     *
     * @param string $frequency
     *
     * @return EmailTemplateSchedule
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get frequency.
     *
     * @return string
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Set date.
     *
     * @param integer $date
     *
     * @return EmailTemplateSchedule
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return integer
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set time.
     *
     * @param string $time
     *
     * @return EmailTemplateSchedule
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time.
     *
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set daily recur day.
     *
     * @param string $dailyRecurDay
     *
     * @return EmailTemplateSchedule
     */
    public function setDailyRecurDay($dailyRecurDay)
    {
        $this->daily_recur_day = $dailyRecurDay;

        return $this;
    }

    /**
     * Get daily recur day.
     *
     * @return string
     */
    public function getDailyRecurDay()
    {
        return $this->daily_recur_day;
    }

    /**
     * Set weekly recure day.
     *
     * @param string $weeklyRecureDay
     *
     * @return EmailTemplateSchedule
     */
    public function setWeeklyRecurDay($weeklyRecureDay)
    {
        $this->weekly_recur_day = $weeklyRecureDay;

        return $this;
    }

    /**
     * Get weekly recure day.
     *
     * @return string
     */
    public function getWeeklyRecurDay()
    {
        return $this->weekly_recur_day;
    }

    /**
     * Set weekly day.
     *
     * @param string $weeklyDay
     *
     * @return EmailTemplateSchedule
     */
    public function setWeeklyDay($weeklyDay)
    {
        $this->weekly_day = $weeklyDay;

        return $this;
    }

    /**
     * Get weekly day.
     *
     * @return string
     */
    public function getWeeklyDay()
    {
        return $this->weekly_day;
    }

    /**
     * Set monthly day.
     *
     * @param string $monthlyDay
     *
     * @return EmailTemplateSchedule
     */
    public function setMonthlyDay($monthlyDay)
    {
        $this->monthly_day = $monthlyDay;

        return $this;
    }

    /**
     * Get monthly day.
     *
     * @return string
     */
    public function getMonthlyDay()
    {
        return $this->monthly_day;
    }

    /**
     * Set month.
     *
     * @param string $month
     *
     * @return EmailTemplateSchedule
     */
    public function setMonth($month)
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get month.
     *
     * @return string
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set after given time.
     *
     * @param string $after_given_time
     *
     * @return EmailTemplateSchedule
     */
    public function setAfterGivenTime($after_given_time)
    {
        $this->after_given_time = $after_given_time;

        return $this;
    }

    /**
     * Get after given time.
     *
     * @return string
     */
    public function getAfterGivenTime()
    {
        return $this->after_given_time;
    }

    /**
     * Set is after given time recurring.
     *
     * @param string $is_after_given_time_recurring
     *
     * @return EmailTemplateSchedule
     */
    public function setISAfterGivenTimeRecurring($is_after_given_time_recurring)
    {
        $this->is_after_given_time_recurring = $is_after_given_time_recurring;

        return $this;
    }

    /**
     * Get is after given time recurring.
     *
     * @return boolean
     */
    public function getISAfterGivenTimeRecurring()
    {
        return $this->is_after_given_time_recurring;
    }

    /**
     * Set email template.
     *
     * @param \Fa\Bundle\EmailBundle\Entity\EmailTemplate $emailTemplate
     *
     * @return EmailTemplateSchedule
     */
    public function setEmailTemplate(\Fa\Bundle\EmailBundle\Entity\EmailTemplate $emailTemplate = null)
    {
        $this->email_template = $emailTemplate;

        return $this;
    }

    /**
     * Get email template.
     *
     * @return \Fa\Bundle\EmailBundle\Entity\EmailTemplate
     */
    public function getEmailTemplate()
    {
        return $this->email_template;
    }
}
