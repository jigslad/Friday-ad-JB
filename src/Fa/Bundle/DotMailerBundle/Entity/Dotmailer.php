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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to dotmailer.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="dotmailer", indexes={@ORM\Index(name="fa_dotmailer_email_index", columns={"email"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Dotmailer
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
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="dotmailer_newsletter_type_id", type="simple_array", nullable=true)
     *
     */
    private $dotmailer_newsletter_type_id;

    /**
     * @var string
     *
     * @ORM\Column(name="dotmailer_newsletter_type_optout_id", type="simple_array", nullable=true)
     *
     */
    private $dotmailer_newsletter_type_optout_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="dotmailer_newsletter_unsubscribe", type="boolean", nullable=true, options={"default" = 0})
     */
    private $dotmailer_newsletter_unsubscribe = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="guid", type="string", length=100, nullable=false)
     */
    private $guid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="opt_in", type="boolean", nullable=true)
     */
    private $opt_in;

    /**
     * @var string
     *
     * @ORM\Column(name="opt_in_type", type="string", length=50, nullable=true)
     */
    private $opt_in_type;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=100, nullable=true)
     */
    private $first_name;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=100, nullable=true)
     */
    private $last_name;

    /**
     * @var string
     *
     * @ORM\Column(name="business_name", type="string", length=100, nullable=true)
     */
    private $business_name;

    /**
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=20, nullable=true)
     */
    private $postcode;

    /**
     * @var string
     *
     * @ORM\Column(name="enq_postcode", type="string", length=20, nullable=true)
     */
    private $enq_postcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="town_id", type="integer", nullable=true)
     */
    private $town_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="county_id", type="integer", nullable=true)
     */
    private $county_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enquiry_town_id", type="integer", nullable=true)
     */
    private $enquiry_town_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enquiry_county_id", type="integer", nullable=true)
     */
    private $enquiry_county_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", nullable=true)
     */
    private $role_id;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=25, nullable=true)
     */
    private $phone;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_paid_at", type="integer", length=10, nullable=true)
     */
    private $last_paid_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_paa_at", type="integer", length=10, nullable=true)
     */
    private $last_paa_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_enquiry_at", type="integer", length=10, nullable=true)
     */
    private $last_enquiry_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="newsletter_signup_at", type="integer", length=10, nullable=true)
     */
    private $newsletter_signup_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_edition_id", type="integer", nullable=true)
     */
    private $print_edition_id;

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
     * @ORM\Column(name="town_text", type="text", nullable=true)
     */
    private $town_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="county_text", type="text", nullable=true)
     */
    private $county_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="paa_entries", type="text", nullable=true)
     */
    private $paa_entries;

    /**
     * @var integer
     *
     * @ORM\Column(name="enqueries_entries", type="text", nullable=true)
     */
    private $enqueries_entries;

    /**
     * @var integer
     *
     * @ORM\Column(name="ti_user", type="boolean", nullable=true, options={"default" = 0})
     */
    private $ti_user = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="fad_user", type="boolean", nullable=true, options={"default" = 0})
     */
    private $fad_user = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_suppressed", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_suppressed = false;

    /**
     * @var string
     *
     * @ORM\Column(name="suppressed_reason", type="string", length=255, nullable=true)
     */
    private $suppressed_reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="business_category_id", type="integer", length=10, nullable=true)
     */
    private $business_category_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_half_account", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_half_account = 0;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_contact_sent", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_contact_sent;

    
    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=4, nullable=true)
     */
    private $gender;
    
    /**
     * @var string
     *
     * @ORM\Column(name="date_of_birth", type="string", length=20, nullable=true)
     */
    private $dateOfBirth;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set updated at value.
     *
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
        $this->updated_at = time();
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
     * @return Message
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
     * Set email.
     *
     * @param string $email
     * @return Dotmailer
     */
    public function setEmail($email)
    {
        $this->email = $email;

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
     * Set guid.
     *
     * @param string $guid
     * @return Dotmailer
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;

        return $this;
    }

    /**
     * Get guid.
     *
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Set opt_in.
     *
     * @param boolean $opt_in
     * @return Dotmailer
     */
    public function setOptIn($opt_in)
    {
        $this->opt_in = $opt_in;

        return $this;
    }

    /**
     * Get opt_in.
     *
     * @return boolean
     */
    public function getOptIn()
    {
        return $this->opt_in;
    }

    /**
     * Set opt_in_type.
     *
     * @param string $opt_in_type
     * @return Dotmailer
     */
    public function setOptInType($opt_in_type)
    {
        $this->opt_in_type = $opt_in_type;

        return $this;
    }

    /**
     * Get opt_in_type.
     *
     * @return string
     */
    public function getOptInType()
    {
        return $this->opt_in_type;
    }

    /**
     * Set first_name.
     *
     * @param string $first_name
     * @return Dotmailer
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Get first_name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name.
     *
     * @param string $last_name
     * @return Dotmailer
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Get last_name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set business_name.
     *
     * @param string $business_name
     * @return Dotmailer
     */
    public function setBusinessName($business_name)
    {
        $this->business_name = $business_name;

        return $this;
    }

    /**
     * Get business_name.
     *
     * @return string
     */
    public function getBusinessName()
    {
        return $this->business_name;
    }

    /**
     * Set town_id.
     *
     * @param integer $town_id
     * @return Dotmailer
     */
    public function setTownId($town_id)
    {
        $this->town_id = $town_id;

        return $this;
    }

    /**
     * Get town_id.
     *
     * @return integer
     */
    public function getTownId()
    {
        return $this->town_id;
    }

    /**
     * Set county_id.
     *
     * @param integer $county_id
     * @return Dotmailer
     */
    public function setCountyId($county_id)
    {
        $this->county_id = $county_id;

        return $this;
    }

    /**
     * Get county_id.
     *
     * @return integer
     */
    public function getCountyId()
    {
        return $this->county_id;
    }

    /**
     * Set role_id.
     *
     * @param integer $role_id
     * @return Dotmailer
     */
    public function setRoleId($role_id)
    {
        $this->role_id = $role_id;

        return $this;
    }

    /**
     * Get role_id.
     *
     * @return integer
     */
    public function getRoleId()
    {
        return $this->role_id;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     * @return Dotmailer
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

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
     * Set last_paid_at.
     *
     * @param integer $last_paid_at
     * @return Dotmailer
     */
    public function setLastPaidAt($last_paid_at)
    {
        $this->last_paid_at = $last_paid_at;

        return $this;
    }

    /**
     * Get last_paid_at.
     *
     * @return integer
     */
    public function getLastPaidAt()
    {
        return $this->last_paid_at;
    }

    /**
     * Set last_paa_at.
     *
     * @param integer $last_paa_at
     * @return Dotmailer
     */
    public function setLastPaaAt($last_paa_at)
    {
        $this->last_paa_at = $last_paa_at;

        return $this;
    }

    /**
     * Get last_paa_at.
     *
     * @return integer
     */
    public function getLastPaaAt()
    {
        return $this->last_paa_at;
    }

    /**
     * Set print_edition_id.
     *
     * @param integer $print_edition_id
     * @return Dotmailer
     */
    public function setPrintEditionId($print_edition_id)
    {
        $this->print_edition_id = $print_edition_id;

        return $this;
    }

    /**
     * Get print_edition_id.
     *
     * @return integer
     */
    public function getPrintEditionId()
    {
        return $this->print_edition_id;
    }

    /**
     * Set created_at.
     *
     * @param integer $created_at
     * @return Dotmailer
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
     * @return Dotmailer
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
     * Set last_enquiry_at.
     *
     * @param integer $last_enquiry_at
     * @return Dotmailer
     */
    public function setLastEnquiryAt($last_enquiry_at)
    {
        $this->last_enquiry_at = $last_enquiry_at;

        return $this;
    }

    /**
     * Get last_enquiry_at.
     *
     * @return integer
     */
    public function getLastEnquiryAt()
    {
        return $this->last_enquiry_at;
    }

    /**
     * Set newsletter_signup_at.
     *
     * @param integer $newsletter_signup_at
     * @return Dotmailer
     */
    public function setNewsletterSignupAt($newsletter_signup_at)
    {
        $this->newsletter_signup_at = $newsletter_signup_at;

        return $this;
    }

    /**
     * Get newsletter_signup_at.
     *
     * @return integer
     */
    public function getNewsletterSignupAt()
    {
        return $this->newsletter_signup_at;
    }

    /**
     * Set dotmailer_newsletter_type_id.
     *
     * @param string $dotmailer_newsletter_type_id
     * @return Dotmailer
     */
    public function setDotmailerNewsletterTypeId($dotmailer_newsletter_type_id)
    {
        $this->dotmailer_newsletter_type_id = $dotmailer_newsletter_type_id;

        return $this;
    }

    /**
     * Get dotmailer_newsletter_type_id.
     *
     * @return string
     */
    public function getDotmailerNewsletterTypeId()
    {
        return $this->dotmailer_newsletter_type_id;
    }

    /**
     * Set dotmailer_newsletter_type_optout_id.
     *
     * @param string $dotmailer_newsletter_type_optout_id
     * @return Dotmailer
     */
    public function setDotmailerNewsletterTypeOptoutId($dotmailer_newsletter_type_optout_id)
    {
        $this->dotmailer_newsletter_type_optout_id = $dotmailer_newsletter_type_optout_id;

        return $this;
    }

    /**
     * Get dotmailer_newsletter_type_optout_id.
     *
     * @return string
     */
    public function getDotmailerNewsletterTypeOptoutId()
    {
        return $this->dotmailer_newsletter_type_optout_id;
    }

    /**
     * Set dotmailer_newsletter_unsubscribe
     *
     * @param boolean $dotmailer_newsletter_unsubscribe
     * @return Dotmailer
     */
    public function setDotmailerNewsletterUnsubscribe($dotmailer_newsletter_unsubscribe)
    {
        $this->dotmailer_newsletter_unsubscribe = $dotmailer_newsletter_unsubscribe;

        return $this;
    }

    /**
     * Get dotmailer_newsletter_type_id.
     *
     * @return boolean
     */
    public function getDotmailerNewsletterUnsubscribe()
    {
        return $this->dotmailer_newsletter_unsubscribe;
    }

    /**
     * Set enquiry_town_id.
     *
     * @param integer $enquiry_town_id
     * @return Dotmailer
     */
    public function setEnquiryTownId($enquiry_town_id)
    {
        $this->enquiry_town_id = $enquiry_town_id;

        return $this;
    }

    /**
     * Get enquiry_town_id.
     *
     * @return integer
     */
    public function getEnquiryTownId()
    {
        return $this->enquiry_town_id;
    }

    /**
     * Set enquiry_county_id.
     *
     * @param integer $enquiry_county_id
     * @return Dotmailer
     */
    public function setEnquiryCountyId($enquiry_county_id)
    {
        $this->enquiry_county_id = $enquiry_county_id;

        return $this;
    }

    /**
     * Get enquiry_county_id.
     *
     * @return integer
     */
    public function getEnquiryCountyId()
    {
        return $this->enquiry_county_id;
    }

    /**
     * Set postcode.
     *
     * @param integer $postcode
     * @return Dotmailer
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode.
     *
     * @return integer
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set enq_postcode.
     *
     * @param integer $postcode
     * @return Dotmailer
     */
    public function setEnqPostcode($enq_postcode)
    {
        $this->enq_postcode = $enq_postcode;

        return $this;
    }

    /**
     * Get enq_postcode.
     *
     * @return integer
     */
    public function getEnqPostcode()
    {
        return $this->enq_postcode;
    }

    /**
     * Set town_text.
     *
     * @param integer $town_text
     * @return Dotmailer
     */
    public function setTownText($town_text)
    {
        $this->town_text = $town_text;

        return $this;
    }

    /**
     * Get town_text.
     *
     * @return integer
     */
    public function getTownText()
    {
        return $this->town_text;
    }

    /**
     * Set county_text.
     *
     * @param integer $county_text
     * @return Dotmailer
     */
    public function setCountyText($county_text)
    {
        $this->county_text = $county_text;

        return $this;
    }

    /**
     * Get county_text.
     *
     * @return integer
     */
    public function getCountyText()
    {
        return $this->county_text;
    }

    /**
     * Set paa_entries.
     *
     * @param integer $paa_entries
     * @return Dotmailer
     */
    public function setPaaEntries($paa_entries)
    {
        $this->paa_entries = $paa_entries;

        return $this;
    }

    /**
     * Get paa_entries.
     *
     * @return integer
     */
    public function getPaaEntries()
    {
        return $this->paa_entries;
    }

    /**
     * Set enqueries_entries.
     *
     * @param integer $enqueries_entries
     * @return Dotmailer
     */
    public function setEnqueriesEntries($enqueries_entries)
    {
        $this->enqueries_entries = $enqueries_entries;

        return $this;
    }

    /**
     * Get enqueries_entries.
     *
     * @return integer
     */
    public function getEnqueriesEntries()
    {
        return $this->enqueries_entries;
    }

    /**
     * Set ti_user.
     *
     * @param string $ti_user
     * @return Dotmailer
     */
    public function setTiUser($ti_user)
    {
        $this->ti_user = $ti_user;

        return $this;
    }

    /**
     * Get ti_user.
     *
     * @return string
     */
    public function getTiUser()
    {
        return $this->ti_user;
    }

    /**
     * Set fad_user.
     *
     * @param string $fad_user
     * @return Dotmailer
     */
    public function setFadUser($fad_user)
    {
        $this->fad_user = $fad_user;

        return $this;
    }

    /**
     * Get fad_user.
     *
     * @return string
     */
    public function getFadUser()
    {
        return $this->fad_user;
    }

    /**
     * Set is_suppressed.
     *
     * @param string $is_suppressed
     * @return Dotmailer
     */
    public function setIsSuppressed($is_suppressed)
    {
        $this->is_suppressed = $is_suppressed;

        return $this;
    }

    /**
     * Get is_suppressed.
     *
     * @return string
     */
    public function getIsSuppressed()
    {
        return $this->is_suppressed;
    }

    /**
     * Set suppressed_reason.
     *
     * @param string $suppressed_reason
     * @return Dotmailer
     */
    public function setSuppressedReason($suppressed_reason)
    {
        $this->suppressed_reason = $suppressed_reason;

        return $this;
    }

    /**
     * Get suppressed_reason.
     *
     * @return string
     */
    public function getSuppressedReason()
    {
        return $this->suppressed_reason;
    }

    /**
     * Set business category id
     *
     * @param integer $businessCategoryId
     * @return Dotmailer
     */
    public function setBusinessCategoryId($businessCategoryId)
    {
        $this->business_category_id = $businessCategoryId;

        return $this;
    }

    /**
     * Get business category id
     *
     * @return integer
     */
    public function getBusinessCategoryId()
    {
        return $this->business_category_id;
    }

    /**
     * Set is_half_account.
     *
     * @param string $is_half_account
     * @return Dotmailer
     */
    public function setIsHalfAccount($is_half_account)
    {
        $this->is_half_account = $is_half_account;

        return $this;
    }

    /**
     * Get is_half_account.
     *
     * @return boolean
     */
    public function getIsHalfAccount()
    {
        return $this->is_half_account;
    }
    
    /**
     * Set is_contact_sent.
     *
     * @param boolean $is_contact_sent
     * @return Dotmailer
     */
    public function setIsContactSent($is_contact_sent)
    {
        $this->is_contact_sent = $is_contact_sent;
        
        return $this;
    }
     
    /**
     * Get is_contact_sent.
     *
     * @return boolean
     */
    public function getIsContactSent()
    {
        return $this->is_contact_sent;
    }

    /**     
     * Set gender.
     *
     * @param string $gender
     * @return Dotmailer
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
        
        return $this;
    }
    
    /**
     * Get gender.
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }
    
    /**
     * Set dateOfBirth.
     *
     * @param string $dateOfBirth
     * @return Dotmailer
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth= $dateOfBirth;
        
        return $this;
    }
    
    /**
     * Get dateOfBirth.
     *
     * @return string
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }
}
