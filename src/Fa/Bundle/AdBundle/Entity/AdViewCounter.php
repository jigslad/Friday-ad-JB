<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\AdBundle\Entity\AdViewCounter
 *
 * This table is used to store information about ad view counter.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_view_counter", indexes={@ORM\Index(name="fa_ad_view_counter_created_at_idx", columns={"created_at"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdViewCounterRepository")
 */
class AdViewCounter
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
     * @ORM\Column(name="hits", type="smallint", length=4, nullable=true)
     */
    private $hits;

    /**
     * @var integer
     *
     * @ORM\Column(name="list_views", type="smallint", length=4, nullable=true)
     */
    private $list_views;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

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
     * Set hits
     *
     * @param integer $hits
     * @return AdViewCounter
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return integer
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set list_views
     *
     * @param integer $listViews
     * @return AdViewCounter
     */
    public function setListViews($listViews)
    {
        $this->list_views = $listViews;

        return $this;
    }

    /**
     * Get list_views
     *
     * @return integer
     */
    public function getListViews()
    {
        return $this->list_views;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return AdViewCounter
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
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return AdViewCounter
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
}
