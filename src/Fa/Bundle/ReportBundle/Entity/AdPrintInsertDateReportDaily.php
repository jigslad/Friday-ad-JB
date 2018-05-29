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
 * This table is used to store information related to ad report.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_print_insert_date_report_daily", indexes={@ORM\Index(name="fa_report_ad_print_insert_date_report_daily_ad_id_index", columns={"ad_id"}), @ORM\Index(name="fa_report_ad_print_insert_date_report_daily_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_report_ad_print_insert_date_report_daily_index", columns={"print_insert_date"}), @ORM\Index(name="fa_report_ad_print_ad_report_daily_id_index", columns={"ad_report_daily_id"}), @ORM\Index(name="fa_report_ad_print_print_edition_id_index", columns={"print_edition_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\ReportBundle\Repository\AdPrintInsertDateReportDailyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdPrintInsertDateReportDaily
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
     * @ORM\Column(name="ad_report_daily_id", type="integer")
     */
    private $ad_report_daily_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_insert_date", type="integer")
     */
    private $print_insert_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_edition_id", type="integer")
     */
    private $print_edition_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", nullable=true)
     */
    private $created_at;

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
     * Set print_edition_id.
     *
     * @param string $print_edition_id
     * @return AdPrintInsertDateReportDaily
     */
    public function setPrintEditionId($print_edition_id)
    {
        $this->print_edition_id = $print_edition_id;

        return $this;
    }

    /**
     * Get print_edition_id.
     *
     * @return string
     */
    public function getPrintEditionId()
    {
        return $this->print_edition_id;
    }
}
