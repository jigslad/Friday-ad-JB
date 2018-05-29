<?php

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Fa\Bundle\CoreBundle\Manager\UploadFileManager
 *
 * This class is used upload image.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
trait UploadFileManager
{
    /**
     * @Assert\NotBlank(message="File is required.", groups={"new"})
     * @Assert\File(maxSize="6000000")
     */
    protected $file;

    /**
     * Temparory file
     *
     * @var string
    */
    private $tempFile;

    /**
     * get file field in table
     *
     * @return string
     */
    abstract protected function getFileField();

    /**
     * get uploaded file in file field in table
     *
     * @param string $file
     *
     * @return void;
     */
    abstract protected function setFileField($file);

    /**
     * get file upload directory
     *
     * @return string
    */
    abstract protected function getUploadDir();

    /**
     * get file upload root directory
     *
     * @return string
     */
    abstract protected function getUploadRootDir();

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;

        // Check if we have an old image path
    if (is_file($this->getAbsolutePath())) {
            // Store the old name to delete before the update
            $this->tempFile = $this->getAbsolutePath();
    }
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
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpload()
    {
        if (null !== $this->getFile()) {
            $this->setFileField(uniqid().'.'.$this->getFile()->guessExtension());
        }
    }

    /**
     * @ORM\PostPersist
     * @ORM\PostUpdate
     */
    public function upload()
    {
        if (null === $this->getFile()) {
            return;
        }

        // check if we have an old image
        if (isset($this->tempFile)) {
            unlink($this->tempFile);
            $this->tempFile = null;
        }

        // If there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getFile()->move($this->getUploadRootDir(), $this->getFileField());

        $this->file = null;
    }

    /**
     * @ORM\PostRemove
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function getWebPath()
    {
        return null === $this->getFileField() ? null : $this->getUploadDir().'/'.$this->getFileField();
    }

    public function getAbsolutePath()
    {
        return null === $this->getFileField() ? null : $this->getUploadRootDir().'/'.$this->getFileField();
    }
    }
