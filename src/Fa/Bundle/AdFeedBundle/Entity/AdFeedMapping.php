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

/**
 * This table is used to store various category dimension.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="ad_feed_mapping")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdFeedBundle\Repository\AdFeedMappingRepository")
 */
class AdFeedMapping
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
     * Name.
     *
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=500, nullable=true)
     */
    private $text;

    /**
     * Name.
     *
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=500, nullable=true)
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="ref_site_id", type="integer", nullable=true)
     */
    private $ref_site_id;
    
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
     * @return Entity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return AdFeedMapping
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set target.
     *
     * @param string $target
     *
     * @return AdFeedMapping
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }
    
    /**
     * Get ref site id.
     *
     * @return string
     */
    public function getRefSiteId()
    {
        return $this->ref_site_id;
    }
    
    /**
     * Set ref site id.
     *
     * @param string $ref_site_id
     *
     * @return \Fa\Bundle\AdFeedBundle\Entity\AdFeed
     */
    public function setRefSiteId($ref_site_id)
    {
        $this->ref_site_id = $ref_site_id;
        return $this;
    }
}
