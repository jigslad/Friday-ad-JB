<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store search performed by user.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_search_agent")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserSearchAgentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserSearchAgent
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
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    private $name;

    /**
     * @var text
     *
     * @ORM\Column(name="criteria", type="text", nullable=true)
     */
    private $criteria;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_email_alerts", type="boolean", nullable=true, options={"default" = 1})
     */
    private $is_email_alerts;

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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * @var text
     *
     * @ORM\Column(name="old_criteria", type="text", nullable=true)
     */
    private $old_criteria;

    /**
     * @var text
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="lastest_result_date", type="integer", length=10, nullable=true)
     */
    private $lastest_result_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_run_time_date", type="integer", length=10, nullable=true)
     */
    private $last_run_time_date;

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
     * @return UserSearchAgent
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
     * Set criteria
     *
     * @param string $criteria
     * @return UserSearchAgent
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Get criteria
     *
     * @return string
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return UserSearchAgent
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
     * Set is_email_alerts
     *
     * @param boolean $isEmailAlerts
     * @return UserSearchAgent
     */
    public function setIsEmailAlerts($isEmailAlerts)
    {
        $this->is_email_alerts = $isEmailAlerts;

        return $this;
    }

    /**
     * Get is_email_alerts
     *
     * @return boolean
     */
    public function getIsEmailAlerts()
    {
        return $this->is_email_alerts;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return UserSearchAgent
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
     * @return UserSearchAgent
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
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserSearchAgent
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getOldCriteria()
    {
        return $this->old_criteria;
    }

    /**
     * Set old_criteria
     *
     * @param string $old_criteria
     *
     * @return UserSearchAgent
     */
    public function setOldCriteria($old_criteria)
    {
        $this->old_criteria = $old_criteria;
        return $this;
    }

    /**
     * Get user email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set user email
     *
     * @param string $email
     *
     * @return UserSearchAgent
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get lastest_result_date
     *
     * @return integer
     */
    public function getLastestResultDate()
    {
        return $this->lastest_result_date;
    }

    /**
     * Set lastest_result_date
     *
     * @param integer lastest_result_date
     *
     * @return UserSearchAgent
     */
    public function setLastestResultDate($lastest_result_date)
    {
        $this->lastest_result_date = $lastest_result_date;
        return $this;
    }

    /**
     * Get last_run_time_date
     *
     * @return integer
     */
    public function getLastRunTimeDate()
    {
        return $this->last_run_time_date;
    }

    /**
     * Set last_run_time_date
     *
     * @param integer last_run_time_date
     *
     * @return UserSearchAgent
     */
    public function setLastRunTimeDate($last_run_time_date)
    {
        $this->last_run_time_date = $last_run_time_date;
        return $this;
    }
}
