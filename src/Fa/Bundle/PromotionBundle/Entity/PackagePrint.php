<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to package information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="package_print")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\PackagePrintRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class PackagePrint
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
     * @var \Fa\Bundle\PromotionBundle\Entity\Package
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Package", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $package;

    /**
     * @var float
     *
     * @ORM\Column(name="admin_price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     * @Gedmo\Versioned
     */
    private $admin_price;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     * @Gedmo\Versioned
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=10, nullable=true)
     * @Assert\Regex(pattern="/^[a-z0-9 ]+$/i", message="The value {{ value }} is not a valid alpha numeric.")
     * @Assert\Length(max = 8, maxMessage = "Duration cannot be longer than {{ limit }} characters long.")
     * @Gedmo\Versioned
     */
    private $duration;

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
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set created at value.
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
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
     * Set price
     *
     * @param float $price
     * @return Package
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Package
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
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return Package
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

    /**
     * Set duration
     *
     * @param string duration
     * @return Package
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set package
     *
     * @param \Fa\Bundle\UserBundle\Entity\Package $package
     * @return PackageRule
     */
    public function setPackage(\Fa\Bundle\PromotionBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Fa\Bundle\UserBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set admin_price.
     *
     * @param float $admin_price
     * @return PackagePrint
     */
    public function setAdminPrice($admin_price)
    {
        $this->admin_price = $admin_price;

        return $this;
    }

    /**
     * Get admin_price.
     *
     * @return float
     */
    public function getAdminPrice()
    {
        return $this->admin_price;
    }
}
