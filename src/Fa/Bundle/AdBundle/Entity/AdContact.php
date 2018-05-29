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
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="ad_contact", indexes={@ORM\Index(name="fa_ad_contact_trans_id",  columns={"trans_id"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdContactRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdContact
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
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad;

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
     * @var boolean
     *
     * @ORM\Column(name="use_email_response", type="boolean", nullable=true)
     */
    private $use_email_response;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_privacy_number", type="boolean", nullable=true)
     */
    private $use_privacy_number;

    /**
     * Get id.
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set first name.
     *
     * @param string $first_name
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
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
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
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
     * Set Phone.
     *
     * @param string $phone
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return Ad
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
     * Get use_email_response.
     *
     * @return string
     */
    public function getUseEmailResponse()
    {
        return $this->use_email_response;
    }

    /**
     * Set email.
     *
     * @param string $use_email_response
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
     */
    public function setUseEmailResponse($use_email_response)
    {
        $this->use_email_response = $use_email_response;
        return $this;
    }

    /**
     * Get use_privacy_number.
     *
     * @return string
     */
    public function getUsePrivacyNumber()
    {
        return $this->use_privacy_number;
    }

    /**
     * Set use_privacy_number.
     *
     * @param string $use_privacy_number
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdContact
     */
    public function setUsePrivacyNumber($use_privacy_number)
    {
        $this->use_privacy_number = $use_privacy_number;
        return $this;
    }
}
