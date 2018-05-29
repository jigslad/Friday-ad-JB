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

/**
 * This table is used to store information related to ad report.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_print_report_daily", indexes={@ORM\Index(name="fa_report_ad_print_report_daily_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fa_report_ad_print_report_daily_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_ad_print_report_daily_category_id_index", columns={"category_id"}), @ORM\Index(name="fa_report_ad_print_report_daily_role_id_index", columns={"role_id"}), @ORM\Index(name="fa_report_ad_print_report_daily_print_insert_date_index", columns={"print_insert_date"}), @ORM\Index(name="fa_report_ad_print_report_daily_published_at_index", columns={"published_at"}), @ORM\Index(name="fa_report_ad_print_report_daily_revenue_gross_index", columns={"revenue_gross"}), @ORM\Index(name="fa_report_ad_print_report_daily_revenue_net_index", columns={"revenue_net"}), @ORM\Index(name="fa_report_ad_print_report_daily_source_index", columns={"source"}), @ORM\Index(name="fa_report_ad_print_report_daily_package_id_index", columns={"package_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\AdPrintReportDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdPrintReportDaily
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
     * @ORM\Column(name="ad_id", type="integer")
     */
    private $ad_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="smallint", nullable=true)
     */
    private $role_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_insert_date", type="integer", nullable=true)
     */
    private $print_insert_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="published_at", type="integer", nullable=true)
     */
    private $published_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", nullable=true)
     */
    private $expires_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer")
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_edition_ids", type="string", length=50, nullable=true)
     */
    private $print_edition_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var float
     *
     * @ORM\Column(name="revenue_gross", type="float", precision=15, scale=2, nullable=true)
     */
    private $revenue_gross = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="revenue_net", type="float", precision=15, scale=2, nullable=true)
     */
    private $revenue_net = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="package_id", type="integer", nullable=true)
     */
    private $package_id;

    /**
     * @var string
     *
     * @ORM\Column(name="package_name", type="string", length=50, nullable=true)
     */
    private $package_name;

    /**
     * @var string
     *
     * @ORM\Column(name="package_sr_no", type="string", length=50, nullable=true)
     */
    private $package_sr_no;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_latest_entry", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_latest_entry = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", nullable=true)
     */
    private $created_at;

    /**
     * Set id.
     *
     * @param string $id
     * @return AdPrintReportDaily
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ad_id.
     *
     * @param string $ad_id
     * @return AdPrintReportDaily
     */
    public function setAdId($ad_id)
    {
        $this->ad_id = $ad_id;

        return $this;
    }

    /**
     * Get ad_id.
     *
     * @return string
     */
    public function getAdId()
    {
        return $this->ad_id;
    }

    /**
     * Set user_id.
     *
     * @param string $user_id
     * @return AdPrintReportDaily
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set print_insert_date.
     *
     * @param string $print_insert_date
     * @return AdPrintReportDaily
     */
    public function setPrintInsertDate($print_insert_date)
    {
        $this->print_insert_date = $print_insert_date;

        return $this;
    }

    /**
     * Get print_insert_date.
     *
     * @return string
     */
    public function getPrintInsertDate()
    {
        return $this->print_insert_date;
    }

    /**
     * Set published_at.
     *
     * @param string $published_at
     * @return AdPrintReportDaily
     */
    public function setPublishedAt($published_at)
    {
        $this->published_at = $published_at;

        return $this;
    }

    /**
     * Get published_at.
     *
     * @return string
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    /**
     * Set expires_at.
     *
     * @param string $expires_at
     * @return AdPrintReportDaily
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    /**
     * Get expires_at.
     *
     * @return string
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set category_id.
     *
     * @param string $category_id
     * @return AdPrintReportDaily
     */
    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;

        return $this;
    }

    /**
     * Get category_id.
     *
     * @return string
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set print_edition_ids.
     *
     * @param string $print_edition_ids
     * @return AdPrintReportDaily
     */
    public function setPrintEditionIds($print_edition_ids)
    {
        $this->print_edition_ids = $print_edition_ids;

        return $this;
    }

    /**
     * Get print_edition_ids.
     *
     * @return string
     */
    public function getPrintEditionIds()
    {
        return $this->print_edition_ids;
    }

    /**
     * Set source.
     *
     * @param string $source
     * @return AdPrintReportDaily
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set package_id.
     *
     * @param string $package_id
     * @return AdPrintReportDaily
     */
    public function setPackageId($package_id)
    {
        $this->package_id = $package_id;

        return $this;
    }

    /**
     * Get package_id.
     *
     * @return string
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set package_name.
     *
     * @param string $package_name
     * @return AdPrintReportDaily
     */
    public function setPackageName($package_name)
    {
        $this->package_name = $package_name;

        return $this;
    }

    /**
     * Get package_name.
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->package_name;
    }

    /**
     * Set package_sr_no.
     *
     * @param string $package_sr_no
     * @return AdPrintReportDaily
     */
    public function setPackageSrNo($package_sr_no)
    {
        $this->package_sr_no = $package_sr_no;

        return $this;
    }

    /**
     * Get package_sr_no.
     *
     * @return string
     */
    public function getPackageSrNo()
    {
        return $this->package_sr_no;
    }

    /**
     * Set created_at.
     *
     * @param string $created_at
     * @return AdPrintReportDaily
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set role_id.
     *
     * @param string $role_id
     * @return AdPrintReportDaily
     */
    public function setRoleId($role_id)
    {
        $this->role_id = $role_id;

        return $this;
    }

    /**
     * Get role_id.
     *
     * @return string
     */
    public function getRoleId()
    {
        return $this->role_id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return AdReportEnquiry
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set revenue_gross.
     *
     * @param string $revenue_gross
     * @return AdPrintReportDaily
     */
    public function setRevenueGross($revenue_gross)
    {
        $this->revenue_gross = $revenue_gross;

        return $this;
    }

    /**
     * Get revenue_gross.
     *
     * @return string
     */
    public function getRevenueGross()
    {
        return $this->revenue_gross;
    }

    /**
     * Set revenue_net.
     *
     * @param string $revenue_net
     * @return AdPrintReportDaily
     */
    public function setRevenueNet($revenue_net)
    {
        $this->revenue_net = $revenue_net;

        return $this;
    }

    /**
     * Get revenue_net.
     *
     * @return string
     */
    public function getRevenueNet()
    {
        return $this->revenue_net;
    }

    /**
     * Set is_latest_entry.
     *
     * @param string $is_latest_entry
     * @return AdPrintReportDaily
     */
    public function setIsLatestEntry($is_latest_entry)
    {
        $this->is_latest_entry = $is_latest_entry;

        return $this;
    }

    /**
     * Get is_latest_entry.
     *
     * @return string
     */
    public function getIsLatestEntry()
    {
        return $this->is_latest_entry;
    }
}
