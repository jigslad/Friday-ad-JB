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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\CoreBundle\Validator\Constraints\CustomEmail;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store user information of the system.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user", indexes={@ORM\Index(name="fa_user_user_first_name_index", columns={"first_name"}), @ORM\Index(name="fa_user_user_last_name_index", columns={"last_name"}),  @ORM\Index(name="fa_user_user_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_user_user_email_index", columns={"email"}), @ORM\Index(name="fa_user_user_phone_index", columns={"phone"}), @ORM\Index(name="fa_user_user_paypal_email_index", columns={"paypal_email"}), @ORM\Index(name="fa_user_user_username_index", columns={"username"}), @ORM\Index(name="fa_user_user_facebook_id_index", columns={"facebook_id"}), @ORM\Index(name="fa_user_user_google_id_index", columns={"google_id"}), @ORM\Index(name="fa_user_user_last_paa_index", columns={"last_paa"}), @ORM\Index(name="fa_user_user_last_paa_expires_at_index", columns={"last_paa_expires_at"}), @ORM\Index(name="fa_user_user_total_ad_index", columns={"total_ad"}), @ORM\Index(name="fa_user_user_guid_index", columns={"guid"}), @ORM\Index(name="fa_user_user_is_half_account_index", columns={"is_half_account"}), @ORM\Index(name="fa_user_user_business_name_index", columns={"business_name"}), @ORM\Index(name="fa_user_user_old_ti_user_id_index", columns={"old_ti_user_id"}), @ORM\Index(name="fa_user_user_old_ti_user_slug_index", columns={"old_ti_user_slug"})})
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="email", message="An account with this email address already exists.")
 * @ORM\EntityListeners({ "Fa\Bundle\UserBundle\Listener\UserListener" })
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class User implements UserInterface, \Serializable
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
     * @Assert\NotBlank(message="First name is required.", groups={"registration"})
     * @Assert\Length(max="100", min="2", maxMessage="First name can have maximum 30 characters.", minMessage="First name must be at least 2 characters long.", groups={"registration"})
     * @Assert\Regex(pattern="/^[a-z0-9 _-]+$/i", message="First name cannot have special characters other than hyphen and underscore", groups={"registration"})
     * @ORM\Column(name="first_name", type="string", length=100, nullable=true)
     * @Gedmo\Versioned
     */
    private $first_name;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Last name is required.", groups={"registration"})
     * @Assert\Length(max="100", min="2", maxMessage="Last name can have maximum 30 characters.", minMessage="Last name must be at least 2 characters long.", groups={"registration"})
     * @Assert\Regex(pattern="/^[a-z0-9 _-]+$/i", message="Last name cannot have special characters other than hyphen and underscore", groups={"registration"})
     * @ORM\Column(name="last_name", type="string", length=100, nullable=true)
     * @Gedmo\Versioned
     */
    private $last_name;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    private $gender;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=10, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="occupation_name", type="string", length=100, nullable=true)
     */
    private $occupation_name;

    /**
     * @var string
     *
     * @ORM\Column(name="house_name", type="string", length=255, nullable=true)
     */
    private $house_name;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=20, nullable=true)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="cookie_postcode", type="string", length=20, nullable=true)
     */
    private $cookie_postcode;

    /**
     * @var string
     *
     * @ORM\Column(name="town", type="string", length=255, nullable=true)
     */
    private $town;

    /**
     * @var string
     *
     * @ORM\Column(name="county", type="string", length=255, nullable=true)
     */
    private $county;

    /**
     * @var string
     *
     * @ORM\Column(name="road", type="string", length=255, nullable=true)
     */
    private $road;

    /**
     * @var string
     *
     * @ORM\Column(name="local_area", type="string", length=255, nullable=true)
     */
    private $local_area;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", length=255, nullable=true)
     */
    private $area;


    /**
     * @var boolean
     *
     * @ORM\Column(name="via_PAA", type="boolean", nullable=true, options={"default" = 0})
     */
    private $via_PAA;

    /**
     * @var boolean
     *
     * @ORM\Column(name="via_Paa_Lite", type="boolean", nullable=true, options={"default" = 0})
     */
    private $via_Paa_Lite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PAA_reminder", type="boolean", nullable=true, options={"default" = 0})
     */
    private $PAA_reminder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="old_user_id", type="string", length=255, nullable=true)
     */
    private $old_user_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PCDUsers", type="boolean", nullable=true, options={"default" = 0})
     */
    private $PCDUsers;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_feed_user", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_feed_user;

    /**
     * @var boolean
     *
     * @ORM\Column(name="country_code", type="string", length=10, nullable=true)
     */
    private $country_code;

    /**
     * @var boolean
     *
     * @ORM\Column(name="site_source", type="string", length=10, nullable=true)
     */
    private $site_source;

    /**
     * @var boolean
     *
     * @ORM\Column(name="business_type_name", type="string", length=50, nullable=true)
     */
    private $business_type_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="business_name", type="string", length=100, nullable=true)
     * @Gedmo\Versioned
     */
    private $business_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="referrer", type="string", length=50, nullable=true)
     */
    private $referrer;

    /**
     * @var boolean
     *
     * @ORM\Column(name="account_no", type="string", length=10, nullable=true)
     */
    private $account_no;

    /**
     * @var boolean
     *
     * @ORM\Column(name="looking_to_buy", type="string", length=255, nullable=true)
     */
    private $looking_to_buy;

    /**
     * @var boolean
     *
     * @ORM\Column(name="get_paper", type="string", length=255, nullable=true)
     */
    private $get_paper;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mailing_fad", type="boolean", nullable=true, options={"default" = 0})
     */
    private $mailing_fad;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mailing_other", type="boolean", nullable=true, options={"default" = 0})
     */
    private $mailing_other;

    /**
     * @var integer
     *
     * @ORM\Column(name="image_limit", type="integer", length=2, nullable=true)
     */
    private $image_limit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="business_size_name", type="string", length=10, nullable=true)
     */
    private $business_size_name;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Email is required.",  groups={"registration", "user_detail"})
     * @CustomEmail(message="This email {{ value }} is not a valid email.", groups={"registration", "user_detail"})
     * @Assert\Length(max="100", maxMessage="Email can have maximum 100 characters.",  groups={"registration", "user_detail"})
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     * @Gedmo\Versioned
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_lang", type="string", length=5, nullable=true)
     */
    private $mail_lang;

    /**
     * @var string
     *
     * @Assert\Regex(pattern="/^\+?\d{7,11}$/", message="Please enter correct phone number. It should contain minimum 7 digit and maximum 11 digit.", groups={"registration", "user_detail"})
     *
     * @ORM\Column(name="phone", type="string", length=25, nullable=true)
     * @Gedmo\Versioned
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=25, nullable=true)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=25, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @Assert\Url(message="The url '{{ value }}' is not valid url.")
     * @Assert\Length(max="255", maxMessage="Url can have maximum 255 characters.")
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=50, nullable=true)
     * @Gedmo\Versioned
     */
    private $image;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Username is required.")
     * @Assert\Length(max="100", min="2", maxMessage="Username can have maximum 100 characters.", minMessage="First name must be at least 2 characters long.")
     *
     * @ORM\Column(name="username", type="string", length=255)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=128, nullable=true)
     */
    private $salt;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_login", type="integer", length=11, nullable=true)
     */
    private $last_login;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_login", type="integer", length=11, nullable=true)
     */
    private $total_login;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=100, nullable=true)
     */
    private $ip_address;

    /**
     * @var string
     *
     * @ORM\Column(name="company", type="string", length=255, nullable=true)
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=50, nullable=true)
     */
    private $logo;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\User", mappedBy="parent")
     */
    private $child;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User", inversedBy="child")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parent;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @Assert\NotBlank(message="Role is required.", groups={"create_user"})
     *
     * @ORM\ManyToMany(targetEntity="Fa\Bundle\UserBundle\Entity\Role")
     * @ORM\JoinTable(name="user_role",
     *   joinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")
     *   }
     * )
     */
    private $roles;

    /**
     * @var boolean
     *
     * @ORM\Column(name="free_trial_enable", type="boolean", nullable=true, options={"default" = 0})
     */
    private $free_trial_enable;

    private $plainpassword;

    /**
     * @var string
     */
    public $file;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_private_phone_number", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $is_private_phone_number;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paypal_vefiried", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_paypal_vefiried;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_email_alert_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_email_alert_enabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_email_verified", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_email_verified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_facebook_verified", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_facebook_verified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_you_own_car", type="boolean", nullable=true, options={"default" = 0})
     */
    private $do_you_own_car;

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_you_own_home", type="boolean", nullable=true, options={"default" = 0})
     */
    private $do_you_own_home;

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_you_have_children", type="boolean", nullable=true, options={"default" = 0})
     */
    private $do_you_have_children;

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_you_have_pet", type="boolean", nullable=true, options={"default" = 0})
     */
    private $do_you_have_pet;

    /**
     * @var boolean
     *
     * @ORM\Column(name="contact_through_phone", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $contact_through_phone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="contact_through_email", type="boolean", nullable=true, options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $contact_through_email;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_email", type="string", length=100, nullable=true)
     */
    private $paypal_email;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_first_name", type="string", length=150, nullable=true)
     */
    private $paypal_first_name;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_last_name", type="string", length=150, nullable=true)
     */
    private $paypal_last_name;

    /**
     * @var string
     *
     * @ORM\Column(name="privacy_number", type="string", length=15, nullable=true, options={"comment" = "yac"})
     */
    private $privacy_number;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $status;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\Column(name="status_id_old", type="integer", length=2, nullable=true)
     */
    private $status_id_old;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="occupation_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $occupation;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="business_size_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $business_size;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="business_type_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $business_type;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\ChildEntity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="business_sub_type_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $business_sub_type;

    /**
     * @var string
     *
     * @ORM\Column(name="profile_username", type="string", length=255, nullable=true)
     */
    private $profile_username;

    /**
     * @var \Date
     *
     * @ORM\Column(name="birthdate", type="datetime", nullable=true)
     */
    private $birthdate;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\Role
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $role;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     */
    private $facebook_id;

    /**
     * @var string
     *
     * @ORM\Column(name="google_id", type="string", length=255, nullable=true)
     */
    private $google_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="business_category_id", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $business_category_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paid_before", type="boolean", nullable=true)
     */
    private $is_paid_before;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true, options={"default" = 1})
     */
    private $is_active = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=50, nullable=true)
     */
    private $update_type;

    /**
     * @var string
     *
     * @ORM\Column(name="old_meta_xml", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $old_meta_xml;

    /**
     * @var string
     *
     * @ORM\Column(name="about_you", type="text", nullable=true)
     */
    private $about_you;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_paa", type="integer", length=10, nullable=true)
     */
    private $last_paa;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_paa_expires_at", type="integer", length=10, nullable=true)
     */
    private $last_paa_expires_at;

    /**
     * @var string
     *
     * @ORM\Column(name="total_ad", type="integer", nullable=true, options={"default" = 0})
     */
    private $total_ad;

    /**
     * @var string
     *
     * @ORM\Column(name="guid", type="string", length=100, nullable=true)
     */
    private $guid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_half_account", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_half_account = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_third_party_email_alert_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_third_party_email_alert_enabled = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_user_type_changed", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_user_type_changed = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_type_changed_at", type="integer", length=10, nullable=true)
     */
    private $user_type_changed_at;

    /**
     * @var string
     *
     * @ORM\Column(name="user_master_site", type="string", length=2, nullable=true)
     */
    private $user_master_site;

    /**
     * @var string
     *
     * @ORM\Column(name="user_popup", type="boolean", nullable=true, options={"default" = 0})
     */
    private $user_popup = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_ti_user_id", type="integer", nullable=true)
     */
    private $old_ti_user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="old_ti_user_slug", type="string", length=100, nullable=true)
     */
    private $old_ti_user_slug;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->child = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
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
     * This method is called before image upload.
     *
     * @ORM\PrePersist
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            $this->image = uniqid().'.'.$this->file->guessExtension();
        }
    }

    /**
     * This method is called to move uploaded image.
     *
     * @ORM\PostPersist
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // If there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->image);

        unset($this->file);
    }

    /**
     * This method is used to remove uploaded file.
     *
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

    /**
     * Get upload directory.
     *
     * @return string
     */
    protected function getUploadDir()
    {
        return 'uploads/user';
    }

    /**
     * Get root upload directory.
     *
     * @return string
     */
    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    /**
     * Get web path.
     *
     * @return mixed
     */
    public function getWebPath()
    {
        return null === $this->image ? null : $this->getUploadDir().'/'.$this->image;
    }

    /**
     * Get absolute path.
     *
     * @return mixed
     */
    public function getAbsolutePath()
    {
        return null === $this->image ? null : $this->getUploadRootDir().'/'.$this->image;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    /**
     * Serialize basic user information.
     *
     * @return string
     */
    public function serialize()
    {
        return json_encode(
            array($this->username, $this->password, $this->salt,
                $this->roles, $this->id)
        );
    }

    /**
     * Unserializes the given string in the current User object
     * @param serialized
     */
    public function unserialize($serialized)
    {
        list($this->username, $this->password, $this->salt,
            $this->roles, $this->id) = json_decode(
                $serialized
            );
    }

    /**
     * Get payment
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return $this->first_name.' '.$this->last_name;
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
     * Set id
     *
     * @param string $id
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set first_name
     *
     * @param string $firstName
     * @return User
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
     * Set last_name
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->last_name = $lastName;

        return $this;
    }

    /**
     * Get last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return User
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set zip
     *
     * @param string $zip
     * @return User
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
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set mail_lang
     *
     * @param string $mailLang
     * @return User
     */
    public function setMailLang($mailLang)
    {
        $this->mail_lang = $mailLang;

        return $this;
    }

    /**
     * Get mail_lang
     *
     * @return string
     */
    public function getMailLang()
    {
        return $this->mail_lang;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return User
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
     * Set url
     *
     * @param string $url
     * @return User
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return User
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set plain password
     *
     * @param string $password
     * @return User
     */
    public function setPlainPassword($password)
    {
        $this->plainpassword = $password;

        return $this;
    }

    /**
     * Get plain password
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainpassword;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set last_login
     *
     * @param integer $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->last_login = $lastLogin;

        return $this;
    }

    /**
     * Get last_login
     *
     * @return integer
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * Set total_login
     *
     * @param integer $totalLogin
     * @return User
     */
    public function setTotalLogin($totalLogin)
    {
        $this->total_login = $totalLogin;

        return $this;
    }

    /**
     * Get total_login
     *
     * @return integer
     */
    public function getTotalLogin()
    {
        return $this->total_login;
    }

    /**
     * Set ip_address
     *
     * @param string $ipAddress
     * @return User
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    /**
     * Get ip_address
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * Set company
     *
     * @param string $company
     * @return User
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set logo
     *
     * @param string $logo
     * @return User
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return User
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
     * @return User
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
     * Add child
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $child
     * @return User
     */
    public function addChild(\Fa\Bundle\UserBundle\Entity\User $child)
    {
        $this->child[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $child
     */
    public function removeChild(\Fa\Bundle\UserBundle\Entity\User $child)
    {
        $this->child->removeElement($child);
    }

    /**
     * Get child
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * Set parent
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $parent
     * @return User
     */
    public function setParent(\Fa\Bundle\UserBundle\Entity\User $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set location_country
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationCountry
     * @return User
     */
    public function setLocationCountry(\Fa\Bundle\EntityBundle\Entity\Location $locationCountry = null)
    {
        $this->location_country = $locationCountry;

        return $this;
    }

    /**
     * Get location_country
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationCountry()
    {
        return $this->location_country;
    }

    /**
     * Set location_domicile
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationDomicile
     * @return User
     */
    public function setLocationDomicile(\Fa\Bundle\EntityBundle\Entity\Location $locationDomicile = null)
    {
        $this->location_domicile = $locationDomicile;

        return $this;
    }

    /**
     * Get location_domicile
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationDomicile()
    {
        return $this->location_domicile;
    }

    /**
     * Set location_town
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Location $locationTown
     * @return User
     */
    public function setLocationTown(\Fa\Bundle\EntityBundle\Entity\Location $locationTown = null)
    {
        $this->location_town = $locationTown;

        return $this;
    }

    /**
     * Get location_town
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Location
     */
    public function getLocationTown()
    {
        return $this->location_town;
    }

    /**
     * Add roles
     *
     * @param \Fa\Bundle\UserBundle\Entity\Role $roles
     * @return User
     */
    public function addRole(\Fa\Bundle\UserBundle\Entity\Role $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \Fa\Bundle\UserBundle\Entity\Role $roles
     */
    public function removeRole(\Fa\Bundle\UserBundle\Entity\Role $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles->toArray();
    }

    /**
     * Set is_private_phone_number
     *
     * @param boolean $isPrivatePhoneNumber
     * @return User
     */
    public function setIsPrivatePhoneNumber($isPrivatePhoneNumber)
    {
        $this->is_private_phone_number = $isPrivatePhoneNumber;

        return $this;
    }

    /**
     * Get is_private_phone_number
     *
     * @return boolean
     */
    public function getIsPrivatePhoneNumber()
    {
        return $this->is_private_phone_number;
    }

    /**
     * Set is_paypal_vefiried
     *
     * @param boolean $isPaypalVefiried
     * @return User
     */
    public function setIsPaypalVefiried($isPaypalVefiried)
    {
        $this->is_paypal_vefiried = $isPaypalVefiried;

        return $this;
    }

    /**
     * Get is_paypal_vefiried
     *
     * @return boolean
     */
    public function getIsPaypalVefiried()
    {
        return $this->is_paypal_vefiried;
    }

    /**
     * Set is_email_verified
     *
     * @param boolean $isEmailVerified
     * @return User
     */
    public function setIsEmailVerified($isEmailVerified)
    {
        $this->is_email_verified = $isEmailVerified;

        return $this;
    }

    /**
     * Get is_email_verified
     *
     * @return boolean
     */
    public function getIsEmailVerified()
    {
        return $this->is_email_verified;
    }

    /**
     * Set is_facebook_verified
     *
     * @param boolean $isFacebookVerified
     * @return User
     */
    public function setIsFacebookVerified($isFacebookVerified)
    {
        $this->is_facebook_verified = $isFacebookVerified;

        return $this;
    }

    /**
     * Get is_facebook_verified
     *
     * @return boolean
     */
    public function getIsFacebookVerified()
    {
        return $this->is_facebook_verified;
    }

    /**
     * Set free_trail_enable
     *
     * @param boolean $isFacebookVerified
     * @return User
     */
    public function setFreeTrialEnable($free_trial_enable)
    {
        $this->free_trial_enable = $free_trial_enable;

        return $this;
    }

    /**
     * Get free_trail_enable
     *
     * @return boolean
     */
    public function getFreeTrialEnable()
    {
        return $this->free_trial_enable;
    }

    /**
     * Set do_you_own_car
     *
     * @param boolean $doYouOwnCar
     * @return User
     */
    public function setDoYouOwnCar($doYouOwnCar)
    {
        $this->do_you_own_car = $doYouOwnCar;

        return $this;
    }

    /**
     * Get do_you_own_car
     *
     * @return boolean
     */
    public function getDoYouOwnCar()
    {
        return $this->do_you_own_car;
    }

    /**
     * Set do_you_own_home
     *
     * @param boolean $doYouOwnHome
     * @return User
     */
    public function setDoYouOwnHome($doYouOwnHome)
    {
        $this->do_you_own_home = $doYouOwnHome;

        return $this;
    }

    /**
     * Get do_you_own_home
     *
     * @return boolean
     */
    public function getDoYouOwnHome()
    {
        return $this->do_you_own_home;
    }

    /**
     * Set do_you_have_children
     *
     * @param boolean $doYouHaveChildren
     * @return User
     */
    public function setDoYouHaveChildren($doYouHaveChildren)
    {
        $this->do_you_have_children = $doYouHaveChildren;

        return $this;
    }

    /**
     * Get do_you_have_children
     *
     * @return boolean
     */
    public function getDoYouHaveChildren()
    {
        return $this->do_you_have_children;
    }

    /**
     * Set do_you_have_pet
     *
     * @param boolean $doYouHavePet
     * @return User
     */
    public function setDoYouHavePet($doYouHavePet)
    {
        $this->do_you_have_pet = $doYouHavePet;

        return $this;
    }

    /**
     * Get do_you_have_pet
     *
     * @return boolean
     */
    public function getDoYouHavePet()
    {
        return $this->do_you_have_pet;
    }

    /**
     * Set contact_through_phone
     *
     * @param boolean $contactThroughPhone
     * @return User
     */
    public function setContactThroughPhone($contactThroughPhone)
    {
        $this->contact_through_phone = $contactThroughPhone;

        return $this;
    }

    /**
     * Set contact_through_email
     *
     * @param boolean $contactThroughEmail
     * @return User
     */
    public function setContactThroughEmail($contactThroughEmail)
    {
        $this->contact_through_email = $contactThroughEmail;

        return $this;
    }

    /**
     * Get contact_through_phone
     *
     * @return boolean
     */
    public function getContactThroughPhone()
    {
        return $this->contact_through_phone;
    }

    /**
     * Get contact_through_email
     *
     * @return boolean
     */
    public function getContactThroughEmail()
    {
        return $this->contact_through_email;
    }

    /**
     * Set paypal_email
     *
     * @param string $paypalEmail
     * @return User
     */
    public function setPaypalEmail($paypalEmail)
    {
        $this->paypal_email = $paypalEmail;

        return $this;
    }

    /**
     * Get paypal_email
     *
     * @return string
     */
    public function getPaypalEmail()
    {
        return $this->paypal_email;
    }

    /**
     * Set privacy_number
     *
     * @param string $privacyNumber
     * @return User
     */
    public function setPrivacyNumber($privacyNumber)
    {
        $this->privacy_number = $privacyNumber;

        return $this;
    }

    /**
     * Get privacy_number
     *
     * @return string
     */
    public function getPrivacyNumber()
    {
        return $this->privacy_number;
    }

    /**
     * Set profile_username
     *
     * @param string $profileUsername
     * @return User
     */
    public function setProfileUsername($profileUsername)
    {
        $this->profile_username = $profileUsername;

        return $this;
    }

    /**
     * Get profile_username
     *
     * @return string
     */
    public function getProfileUsername()
    {
        return $this->profile_username;
    }

    /**
     * Set birthdate
     *
     * @param \DateTime $birthdate
     * @return User
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Get birthdate
     *
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set status
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $status
     * @return User
     */
    public function setStatus(\Fa\Bundle\EntityBundle\Entity\Entity $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set occupation
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $occupation
     * @return User
     */
    public function setOccupation(\Fa\Bundle\EntityBundle\Entity\Entity $occupation = null)
    {
        $this->occupation = $occupation;

        return $this;
    }

    /**
     * Get occupation
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * Set business_size
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $businessSize
     * @return User
     */
    public function setBusinessSize(\Fa\Bundle\EntityBundle\Entity\Entity $businessSize = null)
    {
        $this->business_size = $businessSize;

        return $this;
    }

    /**
     * Get business_size
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getBusinessSize()
    {
        return $this->business_size;
    }

    /**
     * Set business_type
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $businessType
     * @return User
     */
    public function setBusinessType(\Fa\Bundle\EntityBundle\Entity\Entity $businessType = null)
    {
        $this->business_type = $businessType;

        return $this;
    }

    /**
     * Get business_type
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getBusinessType()
    {
        return $this->business_type;
    }

    /**
     * Set business_sub_type
     *
     * @param \Fa\Bundle\EntityBundle\Entity\ChildEntity $businessSubType
     * @return User
     */
    public function setBusinessSubType(\Fa\Bundle\EntityBundle\Entity\ChildEntity $businessSubType = null)
    {
        $this->business_sub_type = $businessSubType;

        return $this;
    }

    /**
     * Get business_sub_type
     *
     * @return \Fa\Bundle\EntityBundle\Entity\ChildEntity
     */
    public function getBusinessSubType()
    {
        return $this->business_sub_type;
    }

    /**
     * Get phone_private
     *
     * //TODO check and refine this
     *
     * @return boolean
     */
    public function getConfirmationToken()
    {
        return 'XXXX';
    }

    /**
     * Return fullname (firstname lastname) in first letter capital.
     * If fullname is not available, returns company name if exists.
     *
     * @return string fullname
     */
    public function getFullName()
    {
        if ($this->getBusinessName()) {
            return $this->getBusinessName();
        } elseif ($this->getFirstName() && $this->getLastName()) {
            return ucwords($this->getFirstName().' '.$this->getLastName());
        }

        return null;
    }

    /**
     * Get encrypted key
     *
     * @return string
     */
    public function getEncryptedKey()
    {
        return md5($this->getId().$this->getUsername().$this->getPassword());
    }

    /**
     * Set role
     *
     * @param \Fa\Bundle\UserBundle\Entity\Role $role
     * @return User
     */
    public function setRole(\Fa\Bundle\UserBundle\Entity\Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Fa\Bundle\UserBundle\Entity\Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set facebook id
     *
     * @param string $facebook_id
     * @return User
     */
    public function setFacebookId($facebook_id)
    {
        $this->facebook_id = $facebook_id;

        return $this;
    }

    /**
     * Get facebook id
     *
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    /**
     * Set google id
     *
     * @param string $google_id
     * @return User
     */
    public function setGoogleId($google_id)
    {
        $this->google_id = $google_id;

        return $this;
    }

    /**
     * Get google id
     *
     * @return string
     */
    public function getGoogleId()
    {
        return $this->google_id;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return User
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set fax
     *
     * @param string $fax
     * @return User
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get fax
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Set town
     *
     * @param string $town
     * @return User
     */
    public function setTown($town)
    {
        $this->town = $town;

        return $this;
    }

    /**
     * Get town
     *
     * @return string
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * Set county
     *
     * @param string $county
     * @return User
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Get user full name
     *
     * @return string
     */
    public function getUserFullName()
    {
        if ($this->business_name) {
            return $this->business_name;
        } else {
            return $this->first_name.' '.$this->last_name;
        }
    }

    /**
     * get is feed user or not
     *
     * @return boolean
     */
    public function getIsFeedUser()
    {
        return $this->is_feed_user;
    }

    /**
     * set is feed user or not
     *
     * @param boolean $is_feed_user
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function setIsFeedUser($is_feed_user)
    {
        $this->is_feed_user = $is_feed_user;
        return $this;
    }

    /**
     * Set business name
     *
     * @param string $businessName
     * @return User
     */
    public function setBusinessName($businessName)
    {
        $this->business_name = $businessName;

        return $this;
    }

    /**
     * Get business name
     *
     * @return string
     */
    public function getBusinessName()
    {
        return $this->business_name;
    }

    /**
     * Set is_email_alert_enabled
     *
     * @param boolean $isEmailAlertEnabled
     * @return User
     */
    public function setIsEmailAlertEnabled($isEmailAlertEnabled)
    {
        $this->is_email_alert_enabled = $isEmailAlertEnabled;

        return $this;
    }

    /**
     * Get is_email_alert_enabled
     *
     * @return boolean
     */
    public function getIsEmailAlertEnabled()
    {
        return $this->is_email_alert_enabled;
    }

    /**
     * Set business category id
     *
     * @param integer $businessCategoryId
     * @return User
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
     * Set is_paid_before
     *
     * @param boolean $is_paid_before
     * @return User
     */
    public function setIsPaidBefore($is_paid_before)
    {
        $this->is_paid_before = $is_paid_before;

        return $this;
    }

    /**
     * Get is_paid_before
     *
     * @return boolean
     */
    public function getIsPaidBefore()
    {
        return $this->is_paid_before;
    }

    /**
     * Get seller name
     *
     * @return string
     */
    public function getProfileName()
    {
        $sellerName = '';

        if ($this->role && $this->role->getId() == RoleRepository::ROLE_BUSINESS_SELLER_ID && $this->business_name) {
            $sellerName = $this->business_name;
        } else {
            $sellerName = $this->first_name;
            if (strlen(trim($this->last_name))) {
                $sellerName .= ' '.strtoupper(substr($this->last_name, 0, 1)).'.';
            }
            $sellerName = ucwords($sellerName);
        }

        return $sellerName;
    }

    /**
     * Set update_type
     *
     * @param string $update_type
     * @return User
     */
    public function setUpdateType($update_type)
    {
        $this->update_type = $update_type;

        return $this;
    }

    /**
     * Get update_type
     *
     * @return string
     */
    public function getUpdateType()
    {
        return $this->update_type;
    }

    /**
     * Set old_meta_xml
     *
     * @param string $old_meta_xml
     * @return Ad
     */
    public function setOldMetaXml($old_meta_xml)
    {
        $this->old_meta_xml = $old_meta_xml;

        return $this;
    }

    /**
     * Get old_meta_xml
     *
     * @return string
     */
    public function getOldMetaXml()
    {
        return $this->old_meta_xml;
    }

    /**
     * Set about_you.
     *
     * @param string $about_you
     * @return User
     */
    public function setAboutYou($about_you)
    {
        $this->about_you = $about_you;

        return $this;
    }

    /**
     * Get about_you.
     *
     * @return string
     */
    public function getAboutYou()
    {
        return $this->about_you;
    }

    /**
     * Set last_paa.
     *
     * @param integer $last_paa
     * @return User
     */
    public function setLastPaa($last_paa)
    {
        $this->last_paa = $last_paa;

        return $this;
    }

    /**
     * Get last_paa.
     *
     * @return integer
     */
    public function getLastPaa()
    {
        return $this->last_paa;
    }

    /**
     * Set last_paa_expires_at.
     *
     * @param integer $last_paa_expires_at
     * @return User
     */
    public function setLastPaaExpiresAt($last_paa_expires_at)
    {
        $this->last_paa_expires_at = $last_paa_expires_at;

        return $this;
    }

    /**
     * Get last_paa_expires_at.
     *
     * @return integer
     */
    public function getLastPaaExpiresAt()
    {
        return $this->last_paa_expires_at;
    }

    /**
     * Set total_ad.
     *
     * @param integer $total_ad
     * @return User
     */
    public function setTotalAd($total_ad)
    {
        $this->total_ad = $total_ad;

        return $this;
    }

    /**
     * Get total_ad.
     *
     * @return integer
     */
    public function getTotalAd()
    {
        return $this->total_ad;
    }

    /**
     * Set guid.
     *
     * @param string $guid
     * @return User
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
     * Set is_half_account.
     *
     * @param string $is_half_account
     * @return User
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
     * Set paypal_first_name.
     *
     * @param string $paypal_first_name
     * @return User
     */
    public function setPaypalFirstName($paypal_first_name)
    {
        $this->paypal_first_name = $paypal_first_name;

        return $this;
    }

    /**
     * Get paypal_first_name.
     *
     * @return string
     */
    public function getPaypalFirstName()
    {
        return $this->paypal_first_name;
    }

    /**
     * Set paypal_last_name.
     *
     * @param string $paypal_last_name
     * @return User
     */
    public function setPaypalLastName($paypal_last_name)
    {
        $this->paypal_last_name = $paypal_last_name;

        return $this;
    }

    /**
     * Get paypal_last_name.
     *
     * @return string
     */
    public function getPaypalLastName()
    {
        return $this->paypal_last_name;
    }


    /**
     * Set is_third_party_email_alert_enabled.
     *
     * @param string $is_third_party_email_alert_enabled
     * @return User
     */
    public function setIsThirdPartyEmailAlertEnabled($is_third_party_email_alert_enabled)
    {
        $this->is_third_party_email_alert_enabled = $is_third_party_email_alert_enabled;

        return $this;
    }

    /**
     * Get is_third_party_email_alert_enabled.
     *
     * @return string
     */
    public function getIsThirdPartyEmailAlertEnabled()
    {
        return $this->is_third_party_email_alert_enabled;
    }

    /**
     * Set is_user_type_changed.
     *
     * @param string $is_user_type_changed
     * @return User
     */
    public function setIsUserTypeChanged($is_user_type_changed)
    {
        $this->is_user_type_changed = $is_user_type_changed;

        return $this;
    }

    /**
     * Get is_user_type_changed.
     *
     * @return string
     */
    public function getIsUserTypeChanged()
    {
        return $this->is_user_type_changed;
    }


    /**
     * Set updated at value.
     *
     * @ORM\PreUpdate()
     */
    public function setUserTypeChangedAtValue()
    {
        $this->user_type_changed_at = time();
    }


    /**
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return User
     */
    public function setUserTypeChangedAt($userTypeChangedAt)
    {
        $this->user_type_changed_at = $userTypeChangedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return integer
     */
    public function getUserTypeChangedAt()
    {
        return $this->user_type_changed_at;
    }

    /**
     * Set user_master_site.
     *
     * @param string $user_master_site
     * @return User
     */
    public function setUserMasterSite($user_master_site)
    {
        $this->user_master_site = $user_master_site;

        return $this;
    }

    /**
     * Get user_master_site.
     *
     * @return string
     */
    public function getUserMasterSite()
    {
        return $this->user_master_site;
    }

    /**
     * Set user_popup.
     *
     * @param string $user_popup
     * @return User
     */
    public function setUserPopup($user_popup)
    {
        $this->user_popup = $user_popup;

        return $this;
    }

    /**
     * Get user_popup.
     *
     * @return string
     */
    public function getUserPopup()
    {
        return $this->user_popup;
    }

    /**
     * Set old_ti_user_id.
     *
     * @param string $old_ti_user_id
     * @return User
     */
    public function setOldTiUserId($old_ti_user_id)
    {
        $this->old_ti_user_id = $old_ti_user_id;

        return $this;
    }

    /**
     * Get old_ti_user_id.
     *
     * @return string
     */
    public function getOldTiUserId()
    {
        return $this->old_ti_user_id;
    }

    /**
     * Set old_ti_user_slug.
     *
     * @param string $old_ti_user_slug
     * @return User
     */
    public function setOldTiUserSlug($old_ti_user_slug)
    {
        $this->old_ti_user_slug = $old_ti_user_slug;

        return $this;
    }

    /**
     * Get old_ti_user_slug.
     *
     * @return string
     */
    public function getOldTiUserSlug()
    {
        return $this->old_ti_user_slug;
    }

    /**
     * Set via Paa Lite
     *
     * @param string $via_Paa_Lite
     * @return User
     */
    public function setViaPaaLite($via_Paa_Lite)
    {
        $this->via_Paa_Lite = $via_Paa_Lite;

        return $this;
    }

    /**
     * Get via Paa Lite
     *
     * @return string
     */
    public function getViaPaaLite()
    {
        return $this->via_Paa_Lite;
    }
}
