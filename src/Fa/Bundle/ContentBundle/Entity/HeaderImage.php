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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This table is used to store header image information of the system.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="header_image")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\HeaderImageRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\HeaderImageListener" })
 * @ORM\HasLifecycleCallbacks
 */
class HeaderImage
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
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_country;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domicile_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_domicile;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Location
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="town_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $location_town;

    /**
     * @var integer
     *
     * @ORM\Column(name="screen_type", type="integer")
     */
    private $screen_type;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $category;

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
     * @var string
     *
     * @ORM\Column(name="phone_file_name", type="string", length=100, nullable=true)
     */
    private $phone_file_name;

    /**
     * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
     */
    private $phone_file;

    /**
     * @var boolean
     *
     * @Assert\NotBlank(message="Status is required.", groups={"new", "edit"})
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     */
    private $status;

     /** @var integer
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
     * @Assert\NotBlank(message="File is required.", groups={"new"})
     * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(name="right_hand_image_url", type="string", length=255, nullable=true)
     */
    private $right_hand_image_url;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="override_image", type="boolean", nullable=true, options={"default" = 0})
     */
    private $override_image;

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
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return HeaderImage
     */
    public function setPath($path)
    {
        $this->path = $path ? $path : $this->getUploadDir();

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
     * Set file name.
     *
     * @param string $fileName
     *
     * @return HeaderImage
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Get file name.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     *
     * @return HeaderImage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return HeaderImage
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated at.
     *
     * @param integer $updatedAt
     *
     * @return HeaderImage
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set location country.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationCountry
     *
     * @return HeaderImage
     */
    public function setLocationCountry(\Fa\Bundle\EntityBundle\Entity\Location $locationCountry = null)
    {
        $this->location_country = $locationCountry;

        return $this;
    }

    /**
     * Get location country.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationCountry()
    {
        return $this->location_country;
    }

    /**
     * Set location domicile.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationDomicile
     *
     * @return HeaderImage
     */
    public function setLocationDomicile(\Fa\Bundle\EntityBundle\Entity\Location $locationDomicile = null)
    {
        $this->location_domicile = $locationDomicile;

        return $this;
    }

    /**
     * Get location domicile.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationDomicile()
    {
        return $this->location_domicile;
    }

    /**
     * Set location town.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationTown
     *
     * @return HeaderImage
     */
    public function setLocationTown(\Fa\Bundle\EntityBundle\Entity\Location $locationTown = null)
    {
        $this->location_town = $locationTown;

        return $this;
    }

    /**
     * Get location town.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationTown()
    {
        return $this->location_town;
    }


    /**
     * Set category.
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     *
     * @return HeaderImage
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get Category.
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get payment.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return '';
    }

    /**
     * Set created at value.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * Set updated at value.
     *
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
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
    * Get upload file directory.
    *
    * @return string
    */
    public function getUploadDir()
    {
        return 'uploads/headerimage';
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

    /**
     * Get upload file web path.
     *
     * @return string
     */
    public function getWebPath()
    {
        return null === $this->getFileName() ? null : $this->getUploadDir().'/'.$this->getFileName();
    }

    /**
     * Get upload file web absolute path.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return null === $this->getFileName() ? null : $this->getUploadRootDir().'/'.$this->getFileName();
    }

    /**
     * Get upload file web absolute path.
     *
     * @return string
     */
    public function getPhoneFileAbsolutePath()
    {
        return null === $this->getPhoneFileName() ? null : $this->getUploadRootDir().'/'.$this->getPhoneFileName();
    }

    /**
     * Set screen_type.
     *
     * @param string $screen_type
     * @return HeaderImage
     */
    public function setScreenType($screen_type)
    {
        $this->screen_type = $screen_type;

        return $this;
    }

    /**
     * Get screen_type.
     *
     * @return string
     */
    public function getScreenType()
    {
        return $this->screen_type;
    }

    /**
     * Set phone_file_name.
     *
     * @param string $phone_file_name
     * @return HeaderImage
     */
    public function setPhoneFileName($phone_file_name)
    {
        $this->phone_file_name = $phone_file_name;

        return $this;
    }

    /**
     * Get phone_file_name.
     *
     * @return string
     */
    public function getPhoneFileName()
    {
        return $this->phone_file_name;
    }

    /**
     * Set phone_file.
     *
     * @param UploadedFile $phone_file
     * @return HeaderImage
     */
    public function setPhoneFile(UploadedFile $phone_file = null)
    {
        $this->phone_file = $phone_file;

        return $this;
    }

    /**
     * Get phone_file.
     *
     * @return UploadedFile
     */
    public function getPhoneFile()
    {
        return $this->phone_file;
    }


    /**
     * Set right hand image url.
     *
     * @param string $right_hand_image_url
     * @return HomePopularImage
     */
    public function setRightHandImageUrl($right_hand_image_url)
    {
      $this->right_hand_image_url = $right_hand_image_url;

      return $this;
    }

    /**
     * Get right hand image url.
     *
     * @return string
     */
    public function getRightHandImageUrl()
    {
      return $this->right_hand_image_url;
    }
    
    /**
     * Set override_image.
     *
     * @param boolean $override_image
     *
     * @return HeaderImage
     */
    public function setOverrideImage($override_image)
    {
    	$this->override_image = $override_image;
    	
    	return $this;
    }
    
    /**
     * Get override_image.
     *
     * @return boolean
     */
    public function getOverrideImage()
    {
    	return $this->override_image;
    }
    
}
