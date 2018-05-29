<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to dotmailer.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_report_profile_package_daily", indexes={@ORM\Index(name="fa_report_user_report_profile_package_daily_user_id_index", columns={"user_id"}), @ORM\Index(name="fa_report_user_report_profile_package_daily_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_user_report_pp_daily_package_category_id_index", columns={"package_category_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\UserReportProfilePackageDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserReportProfilePackageDaily
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
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", nullable=true)
     */
    private $role_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_id", type="integer", nullable=true)
     */
    private $package_id;

    /**
     * @var string
     *
     * @ORM\Column(name="package_name", type="string", length=255, nullable=true)
     */
    private $package_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_category_id", type="integer", nullable=true)
     */
    private $package_category_id;

    /**
     * @var float
     *
     * @ORM\Column(name="package_price", type="float", precision=15, scale=2, nullable=true)
     */
    private $package_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_cancelled_at", type="integer", length=10)
     */
    private $package_cancelled_at;

    /**
     * @var string
     *
     * @ORM\Column(name="package_remark", type="text", nullable=true)
     */
    private $package_remark;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_trial_package", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_trial_package;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10)
     */
    private $updated_at;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set updated at value.
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
     * Set id.
     *
     * @param integer $id
     *
     * @return Message
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set package_category_id.
     *
     * @param string $package_category_id
     * @return UserReportProfilePackageDaily
     */
    public function setPackageCategoryId($package_category_id)
    {
        $this->package_category_id = $package_category_id;

        return $this;
    }

    /**
     * Get package_category_id.
     *
     * @return string
     */
    public function getPackageCategoryId()
    {
        return $this->package_category_id;
    }
}
