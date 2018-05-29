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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_feed_site")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\AdFeedSiteRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdFeedSite
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
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="ref_site_id", type="integer")
     */
    private $ref_site_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=1)
     */
    private $status;

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
     * Set id.
     *
     * @param integer $id
     *
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get type.
     *
     * @return the string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get ref site id.
     *
     * @return the string
     */
    public function getRefSiteId()
    {
        return $this->ref_site_id;
    }

    /**
     * Set ref site id.
     *
     * @param string $ref_site_id
     */
    public function setRefSiteId($ref_site_id)
    {
        $this->ref_site_id = $ref_site_id;
        return $this;
    }

    /**
     * Get status.
     *
     * @return the string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}
