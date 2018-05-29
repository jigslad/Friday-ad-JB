<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * This table is used to store user information of the system.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_address_book")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserAddressBookRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserAddressBook
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
     * @ORM\Column(name="first_name", type="string", length=30, nullable=true)
     */
    private $first_name;

    /**
     * @var string
     *
     * @ORM\Column(name="street_address", type="string", length=255, nullable=true)
     */
    private $street_address;

    /**
     * @var string
     *
     * @ORM\Column(name="street_address_2", type="string", length=255, nullable=true)
     */
    private $street_address_2;

    /**
     * @var string
     *
     * @ORM\Column(name="town", type="string", length=150, nullable=true)
     */
    private $town;

    /**
     * @var string
     *
     * @ORM\Column(name="county", type="string", length=150, nullable=true)
     */
    private $county;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=150, nullable=true)
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=20, nullable=true)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=15, nullable=true)
     */
    private $phone;

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
     * @var boolean
     *
     * @ORM\Column(name="is_delivery_address", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_delivery_address;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_invoice_address", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_invoice_address;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default" = 0})
     */
    private $status;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * Constructor
     */
    public function __construct()
    {
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set first_name
     *
     * @param string $firstName
     * @return UserAddressBook
     */
    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;

        return $this;
    }

    /**
     * Get first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set street_address
     *
     * @param string $streetAddress
     * @return UserAddressBook
     */
    public function setStreetAddress($streetAddress)
    {
        $this->street_address = $streetAddress;

        return $this;
    }

    /**
     * Get street_address
     *
     * @return string
     */
    public function getStreetAddress()
    {
        return $this->street_address;
    }

    /**
     * Set street_address_2
     *
     * @param string $streetAddress2
     * @return UserAddressBook
     */
    public function setStreetAddress2($streetAddress2)
    {
        $this->street_address_2 = $streetAddress2;

        return $this;
    }

    /**
     * Get street_address_2
     *
     * @return string
     */
    public function getStreetAddress2()
    {
        return $this->street_address_2;
    }

    /**
     * Set zip
     *
     * @param string $zip
     * @return UserAddressBook
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return UserAddressBook
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return UserAddressBook
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
     * @return UserAddressBook
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
     * Set is_delivery_address
     *
     * @param boolean $isDeliveryAddress
     * @return UserAddressBook
     */
    public function setIsDeliveryAddress($isDeliveryAddress)
    {
        $this->is_delivery_address = $isDeliveryAddress;

        return $this;
    }

    /**
     * Get is_delivery_address
     *
     * @return boolean
     */
    public function getIsDeliveryAddress()
    {
        return $this->is_delivery_address;
    }

    /**
     * Set is_invoice_address
     *
     * @param boolean $isInvoiceAddress
     * @return UserAddressBook
     */
    public function setIsInvoiceAddress($isInvoiceAddress)
    {
        $this->is_invoice_address = $isInvoiceAddress;

        return $this;
    }

    /**
     * Get is_invoice_address
     *
     * @return boolean
     */
    public function getIsInvoiceAddress()
    {
        return $this->is_invoice_address;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return UserAddressBook
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserAddressBook
     */
    public function setUser(\Fa\Bundle\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set country.
     *
     * @param string $country
     * @return UserAddressBook
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set county.
     *
     * @param string $county
     * @return UserAddressBook
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county.
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set town.
     *
     * @param string $town
     * @return UserAddressBook
     */
    public function setTown($town)
    {
        $this->town = $town;

        return $this;
    }

    /**
     * Get town.
     *
     * @return string
     */
    public function getTown()
    {
        return $this->town;
    }
}
