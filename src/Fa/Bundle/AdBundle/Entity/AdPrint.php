<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdBundle\Entity\AdPrint
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_print", indexes={ @ORM\Index(name="fa_ad_print_ad_moderate_status", columns={"ad_moderate_status"}), @ORM\Index(name="fa_ad_print_is_paid", columns={"is_paid"}), @ORM\Index(name="fa_ad_print_insert_date", columns={"insert_date"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdPrintRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdPrint
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
     * @var integer
     *
     * @ORM\Column(name="duration", type="string", length=10, nullable=true)
     */
    private $duration;

    /**
     * @var integer
     *
     * @ORM\Column(name="sequence", type="smallint", nullable=true)
     */
    private $sequence;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\PrintEdition
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\PrintEdition")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="print_edition_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $print_edition;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_queue", type="smallint", options={"default" = 0})
     */
    private $print_queue;

    /**
     * @var integer
     *
     * @ORM\Column(name="tmp_print_queue", type="smallint", options={"default" = 0})
     */
    private $tmp_print_queue = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_moderate_status", type="smallint", options={"default" = 0})
     */
    private $ad_moderate_status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paid", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_paid;

    /**
     * @var integer
     *
     * @ORM\Column(name="insert_date", type="integer", length=10, nullable=true)
     */
    private $insert_date;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return AdPrint
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
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     *
     * @return AdPrint
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set printEdition
     *
     * @param \Fa\Bundle\AdBundle\Entity\PrintEdition $printEdition
     *
     * @return AdPrint
     */
    public function setPrintEdition(\Fa\Bundle\AdBundle\Entity\PrintEdition $printEdition = null)
    {
        $this->print_edition = $printEdition;

        return $this;
    }

    /**
     * Get printEdition
     *
     * @return \Fa\Bundle\AdBundle\Entity\PrintEdition
     */
    public function getPrintEdition()
    {
        return $this->print_edition;
    }

    /**
     * Set print queue
     *
     * @param integer $printQueue
     *
     * @return AdPrint
     */
    public function setPrintQueue($printQueue)
    {
        $this->print_queue = $printQueue;

        return $this;
    }

    /**
     * Get print queue
     *
     * @return integer
     */
    public function getPrintQueue()
    {
        return $this->print_queue;
    }

    /**
     * Set tmp print queue
     *
     * @param integer $tmpPrintQueue
     *
     * @return AdPrint
     */
    public function setTmpPrintQueue($tmpPrintQueue)
    {
        $this->tmp_print_queue = $tmpPrintQueue;

        return $this;
    }

    /**
     * Get tmp print queue
     *
     * @return integer
     */
    public function getTmpPrintQueue()
    {
        return $this->tmp_print_queue;
    }

    /**
     * Set ad moderate status
     *
     * @param integer $adModerateStatus
     *
     * @return AdPrint
     */
    public function setAdModerateStatus($adModerateStatus)
    {
        $this->ad_moderate_status = $adModerateStatus;

        return $this;
    }

    /**
     * Get ad moderate status
     *
     * @return integer
     */
    public function getAdModerateStatus()
    {
        return $this->ad_moderate_status;
    }

    /**
     * Set is paid
     *
     * @param integer $isPaid
     *
     * @return AdPrint
     */
    public function setIsPaid($isPaid)
    {
        $this->is_paid = $isPaid;

        return $this;
    }

    /**
     * Get is paid
     *
     * @return integer
     */
    public function getIsPaid()
    {
        return $this->is_paid;
    }

    /**
     * Set insertDate
     *
     * @param integer $insertDate
     *
     * @return AdPrint
     */
    public function setInsertDate($insertDate)
    {
        $this->insert_date = $insertDate;

        return $this;
    }

    /**
     * Get insertDate
     *
     * @return integer
     */
    public function getInsertDate()
    {
        return $this->insert_date;
    }

    /**
     * Set duration.
     *
     * @param string $duration
     * @return AdPrint
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration.
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set sequence.
     *
     * @param integer $sequence
     * @return AdPrint
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence.
     *
     * @return integer
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return Ad
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
}
