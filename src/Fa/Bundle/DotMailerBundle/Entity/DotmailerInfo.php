<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to dotmailer.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="dotmailer_info", indexes={@ORM\Index(name="idx_paa_category_id", columns={"paa_category_id"}), @ORM\Index(name="idx_enquiry_category_id", columns={"enquiry_category_id"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\DotMailerBundle\Repository\DotmailerInfoRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DotmailerInfo
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
     * @ORM\Column(name="segment", type="string", length=100, nullable=false)
     */
    private $segment;

    /**
     * @var integer
     *
     * @ORM\Column(name="paa_category_id", type="integer", nullable=true)
     */
    private $paa_category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enquiry_category_id", type="integer", nullable=true)
     */
    private $enquiry_category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="paa_created_at", type="integer", length=10, nullable=true)
     */
    private $paa_created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="enquiry_created_at", type="integer", length=10, nullable=true)
     */
    private $enquiry_created_at;

    /**
     * @var \Fa\Bundle\DotMailerBundle\Entity\Dotmailer
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\DotMailerBundle\Entity\Dotmailer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dotmailer_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $dotmailer;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer", nullable=true)
     */
    private $ad_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set dotmailer.
     *
     * @param \Fa\Bundle\DotMailerBundle\Entity\Dotmailer $dotmailer
     * @return DotmailerInfo
     */
    public function setDotmailer(\Fa\Bundle\DotMailerBundle\Entity\Dotmailer $dotmailer)
    {
        $this->dotmailer = $dotmailer;

        return $this;
    }

    /**
     * Get dotmailer.
     *
     * @return object
     */
    public function getDotmailer()
    {
        return $this->dotmailer;
    }

    /**
     * Set segment.
     *
     * @param string $segment
     * @return DotmailerInfo
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;

        return $this;
    }

    /**
     * Get segment.
     *
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * Set paa category_id.
     *
     * @param integer $paa_category_id
     * @return DotmailerInfo
     */
    public function setPaaCategoryId($paa_category_id)
    {
        $this->paa_category_id = $paa_category_id;

        return $this;
    }

    /**
     * Get paa category_id.
     *
     * @return integer
     */
    public function getPaaCategoryId()
    {
        return $this->paa_category_id;
    }

    /**
     * Set enquiry category_id.
     *
     * @param integer $enquiry_category_id
     * @return DotmailerInfo
     */
    public function setEnquiryCategoryId($enquiry_category_id)
    {
        $this->enquiry_category_id = $enquiry_category_id;

        return $this;
    }

    /**
     * Get enquiry category_id.
     *
     * @return integer
     */
    public function getEnquiryCategoryId()
    {
        return $this->enquiry_category_id;
    }

    /**
     * Set paa_created_at.
     *
     * @param integer $paa_created_at
     * @return Dotmailer
     */
    public function setPaaCreatedAt($paa_created_at)
    {
        $this->paa_created_at = $paa_created_at;

        return $this;
    }

    /**
     * Get paa created_at.
     *
     * @return integer
     */
    public function getPaaCreatedAt()
    {
        return $this->paa_created_at;
    }

    /**
     * Set enquiry_created_at.
     *
     * @param integer $enquiry_created_at
     * @return Dotmailer
     */
    public function setEnquiryCreatedAt($enquiry_created_at)
    {
        $this->enquiry_created_at = $enquiry_created_at;

        return $this;
    }

    /**
     * Get enquiry created_at.
     *
     * @return integer
     */
    public function getEnquiryCreatedAt()
    {
        return $this->enquiry_created_at;
    }

    /**
     * Set ad_id.
     *
     * @param integer $ad_id
     * @return DotmailerInfo
     */
    public function setAdId($ad_id)
    {
        $this->ad_id = $ad_id;

        return $this;
    }

    /**
     * Get category_id.
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
    }
}
