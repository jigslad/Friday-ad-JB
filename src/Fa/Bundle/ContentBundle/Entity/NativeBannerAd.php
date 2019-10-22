<?php


namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information about native banner details.
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="native_banner_ad")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\NativeBannerAdRepository")
 */

class NativeBannerAd
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
     * @var \Fa\Bundle\ContentBundle\Entity\NativeBanner
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ContentBundle\Entity\NativeBanner")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="native_banner_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Assert\NotBlank(message="native bannder id is required.")
     */
    private $nativeBanner;

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
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank(message="description is required.")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="button", type="text")
     * @Assert\NotBlank(message="button text is required.")
     */
    private $button;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="text")
     * @Assert\NotBlank(message="position is required.")
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="text")
     * @Assert\NotBlank(message="image is required.")
     */
    private $image;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     * @Assert\NotBlank(message="status is required.")
     */
    private $status;

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
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return bool
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

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
     * @return NativeBannerAd
     */
    public function setTitle($title)
    {
        $this->title= $title;

        return $this;
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
     * @return NativeBanner
     */
    public function getNativeBanner()
    {
        return $this->nativeBanner;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param NativeBanner $nativeBanner
     */
    public function setNativeBanner($nativeBanner)
    {
        $this->nativeBanner = $nativeBanner;
    }

    /**
     * @param string $button
     */
    public function setButton($button)
    {
        $this->button = $button;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getButton()
    {
        return $this->button;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }


    /**
     * Set description
     *
     * @param string $description
     * @return NativeBannerAd
     */
    public function setDescription($description)
    {
        $this->description= $description;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set created_at
     *
     * @param date $created_at
     * @return NativeBannerAd
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
     * @return NativeBannerAd
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