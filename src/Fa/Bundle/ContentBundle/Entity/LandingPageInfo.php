<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This table is used to store information about landing page.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="landing_page_info")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\LandingPageInfoRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\LandingPageInfoListener" })
 * @ORM\HasLifecycleCallbacks
 */
class LandingPageInfo
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
     * @ORM\Column(name="ad_type_id", type="integer", nullable=true)
     */
    private $ad_type_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="section_type", type="smallint", options={"default" = 0}, nullable=true)
     */
    private $section_type;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=100, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=100, nullable=true)
     */
    private $file_name;

    /**
     * @Assert\NotBlank(message="File is required.", groups={"new"})
     * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(name="overlay_file_name", type="string", length=100, nullable=true)
     */
    private $overlay_file_name;

    /**
     * @Assert\NotBlank(message="File is required.", groups={"new"})
     * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
     */
    protected $overlay_file;

    /**
     * @var \Fa\Bundle\ContentBundle\Entity\LandingPage
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ContentBundle\Entity\LandingPage", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="landing_page_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $landing_page;

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
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set id.
     *
     * @param integer $id
     *
     * @return LandingPageInfo
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
     * Set landing page
     *
     * @param \Fa\Bundle\ContentBundle\Entity\User $landing_page
     * @return LandingPageInfo
     */
    public function setLandingPage(\Fa\Bundle\ContentBundle\Entity\LandingPage $landingPage = null)
    {
        $this->landing_page = $landingPage;

        return $this;
    }

    /**
     * Get landing page
     *
     * @return \Fa\Bundle\UserBundle\Entity\LandingPage
     */
    public function getLandingPage()
    {
        return $this->landing_page;
    }

    /**
     * Set ad_type_id.
     *
     * @param integer $ad_type_id
     * @return LandingPageInfo
     */
    public function setAdTypeId($ad_type_id)
    {
        $this->ad_type_id = $ad_type_id;

        return $this;
    }

    /**
     * Get ad_type_id.
     *
     * @return integer
     */
    public function getAdTypeId()
    {
        return $this->ad_type_id;
    }

    /**
     * Set category_id.
     *
     * @param integer $category_id
     * @return LandingPageInfo
     */
    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;

        return $this;
    }

    /**
     * Get category_id.
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set section_type.
     *
     * @param integer $section_type
     * @return LandingPageInfo
     */
    public function setSectionType($section_type)
    {
        $this->section_type = $section_type;

        return $this;
    }

    /**
     * Get section_type.
     *
     * @return integer
     */
    public function getSectionType()
    {
        return $this->section_type;
    }

    /**
     * Set path.
     *
     * @param string $path
     * @return LandingPageInfo
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set file_name.
     *
     * @param string $file_name
     * @return LandingPageInfo
     */
    public function setFileName($file_name)
    {
        $this->file_name = $file_name;

        return $this;
    }

    /**
     * Get file_name.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set overlay_file_name.
     *
     * @param string $overlay_file_name
     * @return LandingPageInfo
     */
    public function setOverlayFileName($overlay_file_name)
    {
        $this->overlay_file_name = $overlay_file_name;

        return $this;
    }

    /**
     * Get overlay_file_name.
     *
     * @return string
     */
    public function getOverlayFileName()
    {
        return $this->overlay_file_name;
    }

    /**
     * Set overlay_file.
     *
     * @param string $overlay_file
     * @return LandingPageInfo
     */
    public function setOverlayFile($overlay_file)
    {
        $this->overlay_file = $overlay_file;

        return $this;
    }

    /**
     * Get overlay_file.
     *
     * @return string
     */
    public function getOverlayFile()
    {
        return $this->overlay_file;
    }

    /**
     * Set created_at.
     *
     * @param integer $created_at
     * @return LandingPageInfo
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at.
     *
     * @param integer $updated_at
     * @return LandingPageInfo
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Get updated_at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set path value.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setPathValue()
    {
        $this->path = $this->getUploadDir();
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get upload file directory.
     *
     * @return string
     */
    protected function getUploadDir()
    {
        return 'uploads/landingpage';
    }

    /**
     * Get upload file root directory.
     *
     * @return string
     */
    public function getUploadRootDir()
    {
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }
}
