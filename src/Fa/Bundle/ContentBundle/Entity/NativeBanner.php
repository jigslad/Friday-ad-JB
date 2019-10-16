<?php


namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information about native banner.
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="banner_native")
 */

class NativeBanner
{
    /**
     * @var integer
     *
     * @ORM\Column(name="banner_native_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */

    private $id;
    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Assert\NotBlank(message="Category is required.")
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text")
     * @Assert\NotBlank(message="title is required.")
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="device", type="text")
     */
    private $device;


    /** @var date
     *
     * @ORM\Column(name="created_at", type="date", length=10)
     */
    private $created_at;

    /**
     * @var date
     *
     * @ORM\Column(name="updated_at", type="date", length=10, nullable=true)
     */
    private $updated_at;


    /**
     * NativeBannerAd constructor.
     */
    public function __construct()
    {
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
     * Set title
     *
     * @param string $title
     * @return NativeBanner
     */
    public function setTitle($title)
    {
        $this->title= $title;

        return $this;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set device
     *
     * @param string $device
     * @return NativeBanner
     */
    public function setDevice($device)
    {
        $this->device= $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return string
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return Ad
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set created_at
     *
     * @param date $created_at
     * @return NativeBanner
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return date
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param date $updated_at
     * @return NativeBanner
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return date
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}