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

/**
 * This table is used to store dotmailer filters.
 *
 * @author Smir Amrutya<samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="dotmailer_filter", indexes={@ORM\Index(name="fa_dotmailer_filter_name_idx", columns={"name"}), @ORM\Index(name="fa_dotmailer_filter_is_24h_loop_idx", columns={"is_24h_loop"}), @ORM\Index(name="fa_dotmailer_filter_status_idx", columns={"status"}), @ORM\Index(name="fa_dotmailer_filter_created_by_idx", columns={"created_by"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\DotMailerBundle\Repository\DotmailerFilterRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DotmailerFilter
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
     * @ORM\Column(name="name", type="string", length=20)
     */
    private $name;

    /**
     * @var text
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_24h_loop", type="boolean", options={"default" = 0})
     */
    private $is_24h_loop = 0;

    /**
     * @var text
     *
     * @ORM\Column(name="filters", type="text", nullable=true)
     */
    private $filters;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="smallint", options={"default" = 0}, nullable=true)
     */
    private $status = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="httpcode", type="smallint", options={"default" = 0}, nullable=true)
     */
    private $httpcode = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="created_by", type="string", length=100, nullable=true)
     */
    private $created_by;

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
     * @var integer
     *
     * @ORM\Column(name="address_book_id", type="integer", nullable=true)
     */
    private $address_book_id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="failed_retry_count", type="smallint", options={"default" = 0}, nullable=true)
     */
    private $failed_retry_count = 0;

    /**
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
     * Set id.
     *
     * @param integer $id
     *
     * @return DotmailerFilter
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
     * Set name.
     *
     * @param string $name
     * @return DotmailerFilter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set filters.
     *
     * @param string $filters
     * @return DotmailerFilter
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters.
     *
     * @return string
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set status.
     *
     * @param boolean $status
     * @return DotmailerFilter
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
     * Set created_at.
     *
     * @param integer $created_at
     * @return DotmailerFilter
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
     * @return DotmailerFilter
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
     * Set comment.
     *
     * @param string $comment
     * @return DotmailerFilter
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set is_24h_loop.
     *
     * @param string $is_24h_loop
     * @return DotmailerFilter
     */
    public function setIs24hLoop($is_24h_loop)
    {
        $this->is_24h_loop = $is_24h_loop;

        return $this;
    }

    /**
     * Get is_24h_loop.
     *
     * @return string
     */
    public function getIs24hLoop()
    {
        return $this->is_24h_loop;
    }

    /**
     * Set created_by.
     *
     * @param string $created_by
     * @return DotmailerNewsletterType
     */
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;

        return $this;
    }

    /**
     * Get created_by.
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Set address_book_id.
     *
     * @param integer $address_book_id
     * @return DotmailerFilter
     */
    public function setAddressBookId($address_book_id)
    {
        $this->address_book_id = $address_book_id;

        return $this;
    }

    /**
     * Get address_book_id.
     *
     * @return integer
     */
    public function getAddressBookId()
    {
        return $this->address_book_id;
    }

    /**
     * Set value.
     *
     * @param string $value
     * @return DotmailerFilter
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set httpcode.
     *
     * @param integer $httpcode
     * @return DotmailerResponse
     */
    public function setHttpcode($httpcode)
    {
        $this->httpcode = $httpcode;

        return $this;
    }

    /**
     * Get httpcode.
     *
     * @return integer
     */
    public function getHttpcode()
    {
        return $this->httpcode;
    }

    /**
     * Set failed_retry_count.
     *
     * @param string $failed_retry_count
     * @return DotmailerFilter
     */
    public function setFailedRetryCount($failed_retry_count)
    {
        $this->failed_retry_count = $failed_retry_count;

        return $this;
    }

    /**
     * Get failed_retry_count.
     *
     * @return string
     */
    public function getFailedRetryCount()
    {
        return $this->failed_retry_count;
    }
}
