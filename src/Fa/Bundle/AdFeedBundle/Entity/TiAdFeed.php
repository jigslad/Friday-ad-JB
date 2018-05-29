<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ti_ad_feed", indexes={@ORM\Index(name="fa_feed_trans_id",  columns={"trans_id"}), @ORM\Index(name="fa_feed_unique_idx", columns={"unique_id"}), @ORM\Index(name="fa_feed_ref_site_id",  columns={"ref_site_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\TiAdFeedRepository")
 */
class TiAdFeed
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
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_id", type="string", length=255)
     */
    private $unique_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ref_site_id", type="integer")
     */
    private $ref_site_id;

    /**
     * Set ad_id.
     *
     * @param string $ad_id
     * @return TiAdFeed
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
     * Set trans_id.
     *
     * @param string $trans_id
     * @return TiAdFeed
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;

        return $this;
    }

    /**
     * Get trans_id.
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * Set unique_id.
     *
     * @param string $unique_id
     * @return TiAdFeed
     */
    public function setUniqueId($unique_id)
    {
        $this->unique_id = $unique_id;

        return $this;
    }

    /**
     * Get unique_id.
     *
     * @return string
     */
    public function getUniqueId()
    {
        return $this->unique_id;
    }

    /**
     * Set ref_site_id.
     *
     * @param string $ref_site_id
     * @return TiAdFeed
     */
    public function setRefSiteId($ref_site_id)
    {
        $this->ref_site_id = $ref_site_id;

        return $this;
    }

    /**
     * Get ref_site_id.
     *
     * @return string
     */
    public function getRefSiteId()
    {
        return $this->ref_site_id;
    }
}
