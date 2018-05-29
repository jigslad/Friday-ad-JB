<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\AdBundle\Entity\Shortlist
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_shortlist")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdShortlistRepository")
 */
class AdShortlist
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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Shortlist
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Shortlist")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="shortlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $shortlist;


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
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return AdShortlist
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
     * Set shortlist
     *
     * @param \Fa\Bundle\AdBundle\Entity\Shortlist $shortlist
     * @return AdShortlist
     */
    public function setShortlist(\Fa\Bundle\AdBundle\Entity\Shortlist $shortlist = null)
    {
        $this->shortlist = $shortlist;

        return $this;
    }

    /**
     * Get shortlist
     *
     * @return \Fa\Bundle\AdBundle\Entity\Shortlist
     */
    public function getShortlist()
    {
        return $this->shortlist;
    }
}
