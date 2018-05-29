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
 * This table is used to store home popular image information of the system.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="home_popular_image")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\HomePopularImageRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\HomePopularImageListener" })
 * @ORM\HasLifecycleCallbacks
 */
class HomePopularImage
{
    use \Fa\Bundle\CoreBundle\Manager\UploadFileManager;

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
     * @ORM\Column(name="url", type="string", length=255)
     * @Assert\NotBlank(message="Url is required.", groups={"new", "edit"})
     * @Assert\Url(groups={"new", "edit"})
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=100, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=100)
     */
    private $file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="overlay_file_name", type="string", length=100)
     */
    private $overlay_file_name;

    /**
     * @Assert\NotBlank(message="File is required.", groups={"new"})
     * @Assert\File(maxSize="2M", mimeTypes={"image/jpeg", "image/png", "image/gif", "image/svg+xml"}, groups={"new", "edit"})
     */
    protected $overlay_file;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 1})
     * @Assert\NotBlank(message="Status is required.", groups={"new", "edit"})
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
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Validate canonical url.
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
    protected function getUploadDir()
    {
        return 'uploads/homepopular';
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
     * Get file field.
     *
     * @return string
     */
    public function getFileField()
    {
        return $this->getFileName();
    }

    /**
     * Set file field.
     *
     * @param unknown $file
     * @return Ambigous <\Fa\Bundle\ContentBundle\Entity\HomePopularImage, \Fa\Bundle\ContentBundle\Entity\HomePopularImage>
     */
    public function setFileField($file)
    {
        return $this->setFileName($file);
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return HomePopularImage
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
     * Set file name.
     *
     * @param string $fileName
     *
     * @return HomePopularImage
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
     * @return HomePopularImage
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
     * @return HomePopularImage
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
     * @return HomePopularImage
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
     * Sets overlay file.
     *
     * @param UploadedFile $overlayFile
     */
    public function setOverlayFile(UploadedFile $overlayFile = null)
    {
        $this->overlay_file = $overlayFile;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getOverlayFile()
    {
        return $this->overlay_file;
    }

    /**
     * Set overlay_file_name.
     *
     * @param string $overlay_file_name
     * @return HomePopularImage
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

    public function getAbsolutePathForOverlay()
    {
        return null === $this->getOverlayFileName() ? null : $this->getUploadRootDir().'/'.$this->getOverlayFileName();
    }

    /**
     * @ORM\PostRemove
     */
    public function removeSvgUpload()
    {
        if ($file = $this->getAbsolutePathForOverlay()) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Set url.
     *
     * @param string $url
     * @return HomePopularImage
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
