<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store ad information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="archive_ad_contact")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ArchiveBundle\Repository\ArchiveAdContactRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ArchiveAdContact
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
     * @var \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ArchiveBundle\Entity\ArchiveAd")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="archive_ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $archive_ad;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="First name is required.", groups={"registration"})
     * @Assert\Length(max="100", min="2", maxMessage="First name can have maximum 30 characters.", minMessage="First name must be at least 2 characters long.", groups={"registration"})
     *
     * @ORM\Column(name="first_name", type="string", length=100, nullable=true)
     */
    private $first_name;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Last name is required.", groups={"registration"})
     * @Assert\Length(max="100", min="2", maxMessage="Last name can have maximum 30 characters.", minMessage="Last name must be at least 2 characters long.", groups={"registration"})
     *
     * @ORM\Column(name="last_name", type="string", length=100, nullable=true)
     */
    private $last_name;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Email is required.",  groups={"registration"})
     * @Assert\Email(message="This email {{ value }} is not a valid email.",  groups={"registration"}, strict="true")
     * @Assert\Length(max="100", maxMessage="Email can have maximum 100 characters.",  groups={"registration"})
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @Assert\Length(max="11", min="6", maxMessage="Phone number can have maximum 11 characters.", minMessage="Phone number must be at least 6 characters long.", groups={"registration"})
     * @Assert\Regex(pattern="/\d/", message="Please enter correct phone number.", groups={"registration"})
     *
     * @ORM\Column(name="phone", type="string", length=25, nullable=true)
     */
    private $phone;

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
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=50, nullable=true)
     */
    private $update_type;

    /**
     * Get id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param integer $id
     * @return \Fa\Bundle\AdBundle\Entity\ArchiveAdContact
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get first name.
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set first name.
     *
     * @param string $first_name
     * @return \Fa\Bundle\AdBundle\Entity\ArchiveAdContact
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * Get last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set last name.
     *
     * @param unknown $last_name
     * @return \Fa\Bundle\AdBundle\Entity\ArchiveAdContact
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Get email.
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param unknown $email
     * @return \Fa\Bundle\AdBundle\Entity\ArchiveAdContact
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set phone.
     *
     * @param integer $phone
     * @return \Fa\Bundle\AdBundle\Entity\ArchiveAdContact
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Set archive ad.
     *
     * @param \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd $archive_ad
     * @return ArchiveAdImage
     */
    public function setArchiveAd(\Fa\Bundle\ArchiveBundle\Entity\ArchiveAd $archive_ad = null)
    {
        $this->archive_ad = $archive_ad;

        return $this;
    }

    /**
     * Get archive ad.
     *
     * @return \Fa\Bundle\ArchiveBundle\Entity\ArchiveAd
     */
    public function getArchiveAd()
    {
        return $this->archive_ad;
    }
}
