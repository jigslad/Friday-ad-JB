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
 * This table is used to store dotmailer bulk update response.
 *
 * @author Smir Amrutya<samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="dotmailer_response")
 * @ORM\Entity(repositoryClass="Fa\Bundle\DotMailerBundle\Repository\DotmailerResponseRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DotmailerResponse
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
     * @ORM\Column(name="dotmailer_type", type="smallint", options={"default" = 0}, nullable=true)
     */
    private $dotmailer_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="dotmailer_id", type="integer", nullable=true)
     */
    private $dotmailer_id;

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
     * Set address_book_id.
     *
     * @param string $address_book_id
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
     * @return string
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
     * Set dotmailer_type.
     *
     * @param string $dotmailer_type
     * @return DotmailerResponse
     */
    public function setDotmailerType($dotmailer_type)
    {
        $this->dotmailer_type = $dotmailer_type;

        return $this;
    }

    /**
     * Get dotmailer_type.
     *
     * @return string
     */
    public function getDotmailerType()
    {
        return $this->dotmailer_type;
    }

    /**
     * Set dotmailer_id.
     *
     * @param string $dotmailer_id
     * @return DotmailerResponse
     */
    public function setDotmailerId($dotmailer_id)
    {
        $this->dotmailer_id = $dotmailer_id;

        return $this;
    }

    /**
     * Get dotmailer_id.
     *
     * @return string
     */
    public function getDotmailerId()
    {
        return $this->dotmailer_id;
    }

    /**
     * Set status.
     *
     * @param integer $status
     * @return DotmailerResponse
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
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
}
