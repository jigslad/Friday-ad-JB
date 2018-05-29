<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to ad report.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_report_daily", indexes={@ORM\Index(name="fa_report_ad_report_daily_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fa_report_ad_report_daily_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_ad_report_daily_category_id_index", columns={"category_id"}), @ORM\Index(name="fa_report_ad_report_daily_town_id_index", columns={"town_id"}), @ORM\Index(name="fa_report_ad_report_daily_county_id_index", columns={"county_id"}), @ORM\Index(name="fa_report_ad_report_daily_role_id_index", columns={"role_id"}), @ORM\Index(name="fa_report_ad_report_daily_ad_created_at_index", columns={"ad_created_at"}), @ORM\Index(name="fa_report_ad_report_daily_print_insert_date_index", columns={"print_insert_date"}), @ORM\Index(name="fa_report_ad_report_daily_published_at_index", columns={"published_at"}), @ORM\Index(name="fa_report_ad_report_daily_no_of_photos_index", columns={"no_of_photos"}), @ORM\Index(name="fa_report_ad_report_daily_total_revenue_gross_index", columns={"total_revenue_gross"}), @ORM\Index(name="fa_report_ad_report_daily_print_revenue_gross_index", columns={"print_revenue_gross"}), @ORM\Index(name="fa_report_ad_report_daily_online_revenue_gross_index", columns={"online_revenue_gross"}),@ORM\Index(name="fa_report_ad_report_daily_total_revenue_net_index", columns={"total_revenue_net"}), @ORM\Index(name="fa_report_ad_report_daily_print_revenue_net_index", columns={"print_revenue_net"}), @ORM\Index(name="fa_report_ad_report_daily_online_revenue_net_index", columns={"online_revenue_net"}), @ORM\Index(name="fa_report_ad_report_daily_shop_package_revenue_index", columns={"shop_package_revenue"}), @ORM\Index(name="fa_report_ad_report_daily_admin_user_email_index", columns={"admin_user_email"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\TiReportBundle\Repository\AdReportDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdReportDaily
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
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_created_at", type="integer", nullable=true)
     */
    private $ad_created_at;

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
     * @var boolean
     *
     * @ORM\Column(name="is_edit", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_edit = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_renewed", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_renewed = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_expired", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_expired = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", nullable=true)
     */
    private $expires_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="expired_at", type="integer", nullable=true)
     */
    private $expired_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_id", type="integer")
     */
    private $status_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer")
     */
    private $category_id;

    /**
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=20, nullable=true)
     */
    private $postcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="town_id", type="integer", nullable=true)
     */
    private $town_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="county_id", type="integer", nullable=true)
     */
    private $county_id;

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
     * @var integer
     *
     * @ORM\Column(name="role_id", type="smallint", nullable=true)
     */
    private $role_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="no_of_photos", type="smallint", nullable=true)
     */
    private $no_of_photos;

    /**
     * @var float
     *
     * @ORM\Column(name="total_revenue_gross", type="float", precision=15, scale=2, nullable=true)
     */
    private $total_revenue_gross = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="print_revenue_gross", type="float", precision=15, scale=2, nullable=true)
     */
    private $print_revenue_gross = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="online_revenue_gross", type="float", precision=15, scale=2, nullable=true)
     */
    private $online_revenue_gross = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="total_revenue_net", type="float", precision=15, scale=2, nullable=true)
     */
    private $total_revenue_net = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="print_revenue_net", type="float", precision=15, scale=2, nullable=true)
     */
    private $print_revenue_net = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="online_revenue_net", type="float", precision=15, scale=2, nullable=true)
     */
    private $online_revenue_net = 0;


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
     * @var integer
     *
     * @ORM\Column(name="duration_print", type="smallint", nullable=true)
     */
    private $duration_print;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration_online", type="smallint", nullable=true)
     */
    private $duration_online;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_package_id", type="integer", nullable=true)
     */
    private $shop_package_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_package_name", type="string", length=50, nullable=true)
     */
    private $shop_package_name;

    /**
     * @var float
     *
     * @ORM\Column(name="shop_package_revenue", type="float", precision=15, scale=2, nullable=true)
     */
    private $shop_package_revenue = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="renewed_at", type="integer", nullable=true)
     */
    private $renewed_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="edited_at", type="integer", nullable=true)
     */
    private $edited_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", nullable=true)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="admin_user_email", type="string", length=255, nullable=true)
     */
    private $admin_user_email;

    /**
     * @var string
     *
     * @ORM\Column(name="source_latest", type="string", length=100, nullable=true)
     */
    private $source_latest;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=30, nullable=true)
     */
    private $payment_method;

    /**
     * @var float
     *
     * @ORM\Column(name="ad_price", type="float", precision=15, scale=2, nullable=true)
     */
    private $ad_price;

    /**
     * @var string
     *
     * @ORM\Column(name="skip_payment_reason", type="string", length=255, nullable=true)
     */
    private $skip_payment_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="phones", type="string", length=255, nullable=true)
     */
    private $phones;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_discount_code_used", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_discount_code_used = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_credit_used", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_credit_used = false;

    /**
     * @var string
     *
     * @ORM\Column(name="credit_value", type="text", nullable=true)
     */
    private $credit_value;

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
     * Set ad_id.
     *
     * @param string $ad_id
     * @return AdReportDaily
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
     * Set ad_created_at.
     *
     * @param string $ad_created_at
     * @return AdReportDaily
     */
    public function setAdCreatedAt($ad_created_at)
    {
        $this->ad_created_at = $ad_created_at;

        return $this;
    }

    /**
     * Get ad_created_at.
     *
     * @return string
     */
    public function getAdCreatedAt()
    {
        return $this->ad_created_at;
    }

    /**
     * Set print_insert_date.
     *
     * @param string $print_insert_date
     * @return AdReportDaily
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
     * @return AdReportDaily
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
     * Set is_edit.
     *
     * @param string $is_edit
     * @return AdReportDaily
     */
    public function setIsEdit($is_edit)
    {
        $this->is_edit = $is_edit;

        return $this;
    }

    /**
     * Get is_edit.
     *
     * @return string
     */
    public function getIsEdit()
    {
        return $this->is_edit;
    }

    /**
     * Set is_renewed.
     *
     * @param string $is_renewed
     * @return AdReportDaily
     */
    public function setIsRenewed($is_renewed)
    {
        $this->is_renewed = $is_renewed;

        return $this;
    }

    /**
     * Get is_renewed.
     *
     * @return string
     */
    public function getIsRenewed()
    {
        return $this->is_renewed;
    }

    /**
     * Set is_expired.
     *
     * @param string $is_expired
     * @return AdReportDaily
     */
    public function setIsExpired($is_expired)
    {
        $this->is_expired = $is_expired;

        return $this;
    }

    /**
     * Get is_expired.
     *
     * @return string
     */
    public function getIsExpired()
    {
        return $this->is_expired;
    }

    /**
     * Set expires_at.
     *
     * @param string $expires_at
     * @return AdReportDaily
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
     * Set expired_at.
     *
     * @param string $expired_at
     * @return AdReportDaily
     */
    public function setExpiredAt($expired_at)
    {
        $this->expired_at = $expired_at;

        return $this;
    }

    /**
     * Get expired_at.
     *
     * @return string
     */
    public function getExpiredAt()
    {
        return $this->expired_at;
    }

    /**
     * Set status_id.
     *
     * @param string $status_id
     * @return AdReportDaily
     */
    public function setStatusId($status_id)
    {
        $this->status_id = $status_id;

        return $this;
    }

    /**
     * Get status_id.
     *
     * @return string
     */
    public function getStatusId()
    {
        return $this->status_id;
    }

    /**
     * Set category_id.
     *
     * @param string $category_id
     * @return AdReportDaily
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
     * Set postcode.
     *
     * @param string $postcode
     * @return AdReportDaily
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode.
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set town_id.
     *
     * @param string $town_id
     * @return AdReportDaily
     */
    public function setTownId($town_id)
    {
        $this->town_id = $town_id;

        return $this;
    }

    /**
     * Get town_id.
     *
     * @return string
     */
    public function getTownId()
    {
        return $this->town_id;
    }

    /**
     * Set county_id.
     *
     * @param string $county_id
     * @return AdReportDaily
     */
    public function setCountyId($county_id)
    {
        $this->county_id = $county_id;

        return $this;
    }

    /**
     * Get county_id.
     *
     * @return string
     */
    public function getCountyId()
    {
        return $this->county_id;
    }

    /**
     * Set print_edition_id.
     *
     * @param string $print_edition_ids
     * @return AdReportDaily
     */
    public function setPrintEditionIds($print_edition_ids)
    {
        $this->print_edition_ids = $print_edition_ids;

        return $this;
    }

    /**
     * Get print_edition_id.
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
     * @return AdReportDaily
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
     * Set role_id.
     *
     * @param string $role_id
     * @return AdReportDaily
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
     * Set no_of_photos.
     *
     * @param string $no_of_photos
     * @return AdReportDaily
     */
    public function setNoOfPhotos($no_of_photos)
    {
        $this->no_of_photos = $no_of_photos;

        return $this;
    }

    /**
     * Get no_of_photos.
     *
     * @return string
     */
    public function getNoOfPhotos()
    {
        return $this->no_of_photos;
    }

    /**
     * Set package_id.
     *
     * @param string $package_id
     * @return AdReportDaily
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
     * @return AdReportDaily
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
     * Set duration_print.
     *
     * @param string $duration_print
     * @return AdReportDaily
     */
    public function setDurationPrint($duration_print)
    {
        $this->duration_print = $duration_print;

        return $this;
    }

    /**
     * Get duration_print.
     *
     * @return string
     */
    public function getDurationPrint()
    {
        return $this->duration_print;
    }

    /**
     * Set duration_online.
     *
     * @param string $duration_online
     * @return AdReportDaily
     */
    public function setDurationOnline($duration_online)
    {
        $this->duration_online = $duration_online;

        return $this;
    }

    /**
     * Get duration_online.
     *
     * @return string
     */
    public function getDurationOnline()
    {
        return $this->duration_online;
    }

    /**
     * Set shop_package_id.
     *
     * @param string $shop_package_id
     * @return AdReportDaily
     */
    public function setShopPackageId($shop_package_id)
    {
        $this->shop_package_id = $shop_package_id;

        return $this;
    }

    /**
     * Get shop_package_id.
     *
     * @return string
     */
    public function getShopPackageId()
    {
        return $this->shop_package_id;
    }

    /**
     * Set shop_package_name.
     *
     * @param string $shop_package_name
     * @return AdReportDaily
     */
    public function setShopPackageName($shop_package_name)
    {
        $this->shop_package_name = $shop_package_name;

        return $this;
    }

    /**
     * Get shop_package_name.
     *
     * @return string
     */
    public function getShopPackageName()
    {
        return $this->shop_package_name;
    }

    /**
     * Set shop_package_revenue.
     *
     * @param string $shop_package_revenue
     * @return AdReportDaily
     */
    public function setShopPackageRevenue($shop_package_revenue)
    {
        $this->shop_package_revenue = $shop_package_revenue;

        return $this;
    }

    /**
     * Get shop_package_revenue.
     *
     * @return string
     */
    public function getShopPackageRevenue()
    {
        return $this->shop_package_revenue;
    }

    /**
     * Set package_sr_no.
     *
     * @param string $package_sr_no
     * @return AdReportDaily
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
     * Set renewed_at.
     *
     * @param integer $renewed_at
     * @return AdReportDaily
     */
    public function setRenewedAt($renewed_at)
    {
        $this->renewed_at = $renewed_at;

        return $this;
    }

    /**
     * Get renewed_at.
     *
     * @return integer
     */
    public function getRenewedAt()
    {
        return $this->renewed_at;
    }

    /**
     * Set edited_at.
     *
     * @param integer $edited_at
     * @return AdReportDaily
     */
    public function setEditedAt($edited_at)
    {
        $this->edited_at = $edited_at;

        return $this;
    }

    /**
     * Get edited_at.
     *
     * @return integer
     */
    public function getEditedAt()
    {
        return $this->edited_at;
    }

    /**
     * Set total_revenue_gross.
     *
     * @param string $total_revenue_gross
     * @return AdReportDaily
     */
    public function setTotalRevenueGross($total_revenue_gross)
    {
        $this->total_revenue_gross = $total_revenue_gross;

        return $this;
    }

    /**
     * Get total_revenue_gross.
     *
     * @return string
     */
    public function getTotalRevenueGross()
    {
        return $this->total_revenue_gross;
    }

    /**
     * Set print_revenue_gross.
     *
     * @param string $print_revenue_gross
     * @return AdReportDaily
     */
    public function setPrintRevenueGross($print_revenue_gross)
    {
        $this->print_revenue_gross = $print_revenue_gross;

        return $this;
    }

    /**
     * Get print_revenue_gross.
     *
     * @return string
     */
    public function getPrintRevenueGross()
    {
        return $this->print_revenue_gross;
    }

    /**
     * Set online_revenue_gross.
     *
     * @param string $online_revenue_gross
     * @return AdReportDaily
     */
    public function setOnlineRevenueGross($online_revenue_gross)
    {
        $this->online_revenue_gross = $online_revenue_gross;

        return $this;
    }

    /**
     * Get online_revenue_gross.
     *
     * @return string
     */
    public function getOnlineRevenueGross()
    {
        return $this->online_revenue_gross;
    }

    /**
     * Set total_revenue_net.
     *
     * @param string $total_revenue_net
     * @return AdReportDaily
     */
    public function setTotalRevenueNet($total_revenue_net)
    {
        $this->total_revenue_net = $total_revenue_net;

        return $this;
    }

    /**
     * Get total_revenue_net.
     *
     * @return string
     */
    public function getTotalRevenueNet()
    {
        return $this->total_revenue_net;
    }

    /**
     * Set print_revenue_net.
     *
     * @param string $print_revenue_net
     * @return AdReportDaily
     */
    public function setPrintRevenueNet($print_revenue_net)
    {
        $this->print_revenue_net = $print_revenue_net;

        return $this;
    }

    /**
     * Get print_revenue_net.
     *
     * @return string
     */
    public function getPrintRevenueNet()
    {
        return $this->print_revenue_net;
    }

    /**
     * Set online_revenue_net.
     *
     * @param string $online_revenue_net
     * @return AdReportDaily
     */
    public function setOnlineRevenueNet($online_revenue_net)
    {
        $this->online_revenue_net = $online_revenue_net;

        return $this;
    }

    /**
     * Get online_revenue_net.
     *
     * @return string
     */
    public function getOnlineRevenueNet()
    {
        return $this->online_revenue_net;
    }

    /**
     * Set admin_user_email.
     *
     * @param string $admin_user_email
     * @return AdReportDaily
     */
    public function setAdminUserEmail($admin_user_email)
    {
        $this->admin_user_email = $admin_user_email;

        return $this;
    }

    /**
     * Get admin_user_email.
     *
     * @return string
     */
    public function getAdminUserEmail()
    {
        return $this->admin_user_email;
    }

    /**
     * Set source_latest.
     *
     * @param string $source_latest
     * @return AdReportDaily
     */
    public function setSourceLatest($source_latest)
    {
        $this->source_latest = $source_latest;

        return $this;
    }

    /**
     * Get source_latest.
     *
     * @return string
     */
    public function getSourceLatest()
    {
        return $this->source_latest;
    }

    /**
     * Set payment_method.
     *
     * @param string $payment_method
     * @return AdReportDaily
     */
    public function setPaymentMethod($payment_method)
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    /**
     * Get payment_method.
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * Set ad_price.
     *
     * @param string $ad_price
     * @return AdReportDaily
     */
    public function setAdPrice($ad_price)
    {
        $this->ad_price = $ad_price;

        return $this;
    }

    /**
     * Get ad_price.
     *
     * @return string
     */
    public function getAdPrice()
    {
        return $this->ad_price;
    }

    /**
     * Set skip_payment_reason.
     *
     * @param string $skip_payment_reason
     * @return Payment
     */
    public function setSkipPaymentReason($skip_payment_reason)
    {
        $this->skip_payment_reason = $skip_payment_reason;

        return $this;
    }

    /**
     * Get skip_payment_reason.
     *
     * @return string
     */
    public function getSkipPaymentReason()
    {
        return $this->skip_payment_reason;
    }

    /**
     * Set is_discount_code_used.
     *
     * @param string $is_discount_code_used
     * @return AdReportDaily
     */
    public function setIsDiscountCodeUsed($is_discount_code_used)
    {
        $this->is_discount_code_used = $is_discount_code_used;

        return $this;
    }

    /**
     * Get is_discount_code_used.
     *
     * @return string
     */
    public function getIsDiscountCodeUsed()
    {
        return $this->is_discount_code_used;
    }


    /**
     * Set phones.
     *
     * @param string $phones
     * @return AdReportDaily
     */
    public function setPhones($phones)
    {
        $this->phones = $phones;

        return $this;
    }

    /**
     * Get phones.
     *
     * @return string
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Set is_credit_used.
     *
     * @param string $is_credit_used
     * @return AdReportDaily
     */
    public function setIsCreditUsed($is_credit_used)
    {
        $this->is_credit_used = $is_credit_used;

        return $this;
    }

    /**
     * Get is_credit_used.
     *
     * @return string
     */
    public function getIsCreditUsed()
    {
        return $this->is_credit_used;
    }

    /**
     * Set credit_value.
     *
     * @param string $credit_value
     * @return AdReportDaily
     */
    public function setCreditValue($credit_value)
    {
        $this->credit_value = $credit_value;

        return $this;
    }

    /**
     * Get credit_value.
     *
     * @return string
     */
    public function getCreditValue()
    {
        return $this->credit_value;
    }
}
