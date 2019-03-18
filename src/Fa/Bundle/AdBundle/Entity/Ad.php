<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\Ad
 *
 * This table is used to store ad information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad", indexes={@ORM\Index(name="fa_ad_sold_stat_sold_at_idx", columns={"sold_at"}), @ORM\Index(name="fa_ad_sold_stat_sold_price_idx", columns={"sold_price"}), @ORM\Index(name="fa_ad_ad_created_at_index", columns={"created_at"}), @ORM\Index(name="fa_ad_ad_expires_at_index", columns={"expires_at"}), @ORM\Index(name="fa_ad_trans_id",  columns={"trans_id"}), @ORM\Index(name="fa_ad_ad_ref",  columns={"ad_ref"}), @ORM\Index(name="fa_old_sub_class_id",  columns={"old_sub_class_id"}), @ORM\Index(name="fa_old_class_id",  columns={"old_class_id"}), @ORM\Index(name="fa_update_type",  columns={"update_type"}), @ORM\Index(name="fa_old_status", columns={"old_status"}), @ORM\Index(name="fa_is_trade_ad", columns={"is_trade_ad"}), @ORM\Index(name="fa_ad_ad_edited_at_index", columns={"edited_at"}), @ORM\Index(name="fa_ad_ad_original_created_at_index", columns={"original_created_at"}), @ORM\Index(name="fa_ad_source_idx", columns={"source"}), @ORM\Index(name="fa_ad_source_latest_idx", columns={"source_latest"}), @ORM\Index(name="fa_ad_published_at_idx", columns={"published_at"}), @ORM\Index(name="fa_ad_ad_edit_moderated_at_idx", columns={"ad_edit_moderated_at"}), @ORM\Index(name="fa_ad_ad_original_published_at_index", columns={"original_published_at"}), @ORM\Index(name="fa_ad_ad_is_add_photo_mail_sent_index", columns={"is_add_photo_mail_sent"}), @ORM\Index(name="fa_ad_ti_ad_id_index", columns={"ti_ad_id"}), @ORM\Index(name="idx_updatedat", columns={"updated_at"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\AdBundle\Listener\AdListener" })
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class Ad
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
     * @var \Fa\Bundle\AdBundle\Entity\AdMain
     *
     * @ORM\OneToOne(targetEntity="Fa\Bundle\AdBundle\Entity\AdMain")
     * @ORM\JoinColumn(name="ad_main", referencedColumnName="id", onDelete="CASCADE")
     */
    private $ad_main;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=15, scale=2, nullable=true)
     * @Gedmo\Versioned
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="price_text", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $price_text;

    /**
     * @var string
     *
     * @ORM\Column(name="is_new", type="smallint", nullable=true)
     * @Gedmo\Versioned
     */
    private $is_new;

    /**
     * @var string
     *
     * @ORM\Column(name="affiliate", type="smallint", nullable=true)
     */
    private $affiliate;

    /**
     * @var string
     *
     * @ORM\Column(name="image_count", type="smallint", nullable=true)
     */
    private $image_count;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_privacy_number", type="boolean", nullable=true)
     */
    private $use_privacy_number;

    /**
     * @var boolean
     *
     * @ORM\Column(name="privacy_number", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $privacy_number;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_email_response", type="boolean", nullable=true)
     */
    private $use_email_response;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_map_link", type="boolean", nullable=true)
     */
    private $use_map_link;

    /**
     * @var string
     *
     * @ORM\Column(name="trans_id", type="string", length=255, nullable=true)
     */
    private $trans_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_ref", type="string", length=255, nullable=true)
     */
    private $ad_ref;

    /**
     * @var string
     *
     * @ORM\Column(name="old_status", type="string", length=30, nullable=true)
     */
    private $old_status;

    /**
     * @var string
     *
     * @ORM\Column(name="tamsin_ad_ref", type="string", length=255, nullable=true)
     */
    private $tamsin_ad_ref;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="creation_ip", type="string", length=100, nullable=true)
     */
    private $creation_ip;

    /**
     * @var string
     *
     * @ORM\Column(name="modify_ip", type="string", length=100, nullable=true)
     */
    private $modify_ip;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_video", type="boolean", nullable=true, options={"default" = 0})
     */
    private $has_video;

    /**
     * @var integer
     *
     * @ORM\Column(name="renewed_at", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $renewed_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", nullable=true)
     */
    private $expires_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_renewal_mail_sent", type="smallint", length=4, options={"default" = 0}, nullable=true)
     */
    private $is_renewal_mail_sent = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_add_photo_mail_sent", type="smallint", length=4, options={"default" = 0}, nullable=true)
     */
    private $is_add_photo_mail_sent = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_feed_ad", type="boolean", nullable=true)
     */
    private $is_feed_ad;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_trade_ad", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_trade_ad;

    /**
     * @var integer
     *
     * @ORM\Column(name="renew_count", type="integer", length=2, nullable=true)
     */
    private $renew_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="sold_at", type="integer", length=10, nullable=true)
     */
    private $sold_at;

    /**
     * @var float
     *
     * @ORM\Column(name="sold_price", type="float", precision=15, scale=2, nullable=true)
     */
    private $sold_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_created_at", type="integer", length=10)
     */
    private $original_created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="published_at", type="integer", length=10, nullable=true)
     */
    private $published_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_published_at", type="integer", length=10, nullable=true)
     */
    private $original_published_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $user;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     * @Gedmo\Versioned
     */
    private $status;

    /**
     *
     * @var boolean @ORM\Column(name="is_boosted", type="boolean", nullable=true, options={"default" = 0})
     */

    private $is_boosted;

    /**
     *
     * @var integer @ORM\Column(name="boosted_at", type="integer", length=10, nullable=true, options={"default" = 0})
     */
    
    private $boosted_at;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Entity
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Entity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * })
     * @Gedmo\Versioned
     */
    private $type;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     * @Gedmo\Versioned
     */
    private $category;

    /**
     * @var \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_method_option_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $delivery_method_option;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_method_id", type="integer", length=2, nullable=true)
     * @Gedmo\Versioned
     */
    private $payment_method_id;

    /**
     * @var string
     *
     * @ORM\Column(name="personalized_title", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $personalized_title;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty", type="integer", length=2, nullable=true)
     * @Gedmo\Versioned
     */
    private $qty;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty_sold", type="integer", nullable=true)
     */
    private $qty_sold;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty_sold_total", type="integer", nullable=true)
     */
    private $qty_sold_total;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_cat_id", type="integer", nullable=true)
     */
    private $old_cat_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_class_id", type="integer", length=2, nullable=true)
     */
    private $old_class_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_sub_class_id", type="integer", length=2, nullable=true)
     */
    private $old_sub_class_id;

    /**
     * @var AdLocation[]
     * @ORM\OneToMany(targetEntity="Fa\Bundle\AdBundle\Entity\AdLocation", mappedBy="ad")
     */
    private $ad_locations;

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=50, nullable=true)
     */
    private $update_type;

    /**
     * @var string
     *
     * @ORM\Column(name="price_old_text", type="string", length=50, nullable=true)
     */
    private $price_old_text;

    /**
     * @var string
     *
     * @ORM\Column(name="old_meta_xml", type="text", nullable=true)
     */
    private $old_meta_xml;

    /**
     * @var string
     *
     * @ORM\Column(name="rejected_reason", type="text", nullable=true)
     */
    private $rejected_reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="weekly_refresh_at", type="integer", length=10, nullable=true)
     */
    private $weekly_refresh_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="manual_refresh", type="boolean", nullable=true, options={"default" = 0})
     */
    private $manual_refresh;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paid_ad", type="boolean", nullable=true)
     */
    private $is_paid_ad;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_blocked_ad", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_blocked_ad = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="postage_price", type="float", precision=15, scale=2, nullable=true, options={"default" = 0})
     */
    private $postage_price = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=25, nullable=true)
     * @Gedmo\Versioned
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     */
    private $skip_solr;

    /**
     * @var integer
     *
     * @ORM\Column(name="edited_at", type="integer", nullable=true)
     */
    private $edited_at;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true, options={"default" = "paa"})
     * @Gedmo\Versioned
     */
    private $source = 'paa';

    /**
     * @var string
     *
     * @ORM\Column(name="source_latest", type="string", length=100, nullable=true)
     * @Gedmo\Versioned
     */
    private $source_latest;

    /**
     * @var integer
     *
     * @ORM\Column(name="admin_user_id", type="integer", nullable=true)
     */
    private $admin_user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="track_back_url", type="string", length=500, nullable=true)
     */
    private $track_back_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="future_publish_at", type="integer", length=11, nullable=true)
     * @Gedmo\Versioned
     */
    private $future_publish_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_future_publish_at", type="integer", length=11, nullable=true)
     */
    private $old_future_publish_at;

    /**
     * @var string
     *
     * @ORM\Column(name="business_phone", type="string", length=25, nullable=true)
     * @Gedmo\Versioned
     */
    private $business_phone;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_edit_moderated_at", type="integer", length=10, nullable=true)
     */
    private $ad_edit_moderated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="youtube_video_url", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $youtube_video_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="ti_ad_id", type="integer", nullable=true)
     */
    private $ti_ad_id;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Campaigns
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Campaigns")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="campaign_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $campaign;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ad_locations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * @ORM\PrePersist()
     */
    public function setOriginalCreatedAtValue()
    {
        $this->original_created_at = time();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Ad
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set ad main.
     *
     * @param \Fa\Bundle\AdBundle\Entity\AdMain $adMain
     *
     * @return Ad
     */
    public function setAdMain(\Fa\Bundle\AdBundle\Entity\AdMain $adMain = null)
    {
        $this->ad_main = $adMain;

        return $this;
    }

    /**
     * Get ad main
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAdMain()
    {
        return $this->ad_main;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return Ad
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set postage price
     *
     * @param float $price_price
     * @return Ad
     */
    public function setPostagePrice($price_price)
    {
        $this->postage_price = $price_price;

        return $this;
    }

    /**
     * Get postage price
     *
     * @return float
     */
    public function getPostagePrice()
    {
        return $this->postage_price;
    }

    /**
     * Set price_text
     *
     * @param float $priceText
     * @return Ad
     */
    public function setPriceText($priceText)
    {
        $this->price_text = $priceText;

        return $this;
    }

    /**
     * Get price_text
     *
     * @return string
     */
    public function getPriceText()
    {
        return $this->price_text;
    }

    /**
     * Set is_new
     *
     * @param string $isNew
     * @return Ad
     */
    public function setIsNew($isNew)
    {
        $this->is_new = $isNew;

        return $this;
    }

    /**
     * Get is_new
     *
     * @return string
     */
    public function getIsNew()
    {
        return $this->is_new;
    }

    /**
     * Set image_count
     *
     * @param string $image_count
     * @return Ad
     */
    public function setImageCount($image_count)
    {
        $this->image_count = $image_count;

        return $this;
    }

    /**
     * Get image_count
     *
     * @return string
     */
    public function getImageCount()
    {
        return $this->image_count;
    }

    /**
     * Set affiliate
     *
     * @param string affiliate
     * @return Ad
     */
    public function setAffiliate($affiliate)
    {
        $this->affiliate = $affiliate;

        return $this;
    }

    /**
     * Get $affiliate
     *
     * @return string
     */
    public function getAffiliate()
    {
        return $this->affiliate;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Ad
     */
    public function setTitle($title)
    {
        if ($title) {
            $title = strip_tags(htmlspecialchars_decode($title), '');
        }
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        $this->title = strip_tags(htmlspecialchars_decode($this->title), '');
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Ad
     */
    public function setDescription($description)
    {
        if ($description) {
            //$description = CommonManager::stripTagsContent(htmlspecialchars_decode($description), '<em><strong><b><i><u><p><ul><li><ol><div><span><br>');
            //$description = CommonManager::removeEmptyTagsRecursive($description);
            $description = strip_tags(htmlspecialchars_decode($description), '<em><strong><b><i><u><p><ul><li><ol><div><span><br>');
        }
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        //$this->description = CommonManager::stripTagsContent(htmlspecialchars_decode($this->description), '<em><strong><b><i><u><p><ul><li><ol><div><span><br>');
        $this->description = strip_tags(htmlspecialchars_decode($this->description), '<em><strong><b><i><u><p><ul><li><ol><div><span><br>');
        return $this->description;
    }

    /**
     * Set creation_ip
     *
     * @param string $creationIp
     * @return Ad
     */
    public function setCreationIp($creationIp)
    {
        $this->creation_ip = $creationIp;

        return $this;
    }

    /**
     * Get creation_ip
     *
     * @return string
     */
    public function getCreationIp()
    {
        return $this->creation_ip;
    }

    /**
     * Set modify_ip
     *
     * @param string $modifyIp
     * @return Ad
     */
    public function setModifyIp($modifyIp)
    {
        $this->modify_ip = $modifyIp;

        return $this;
    }

    /**
     * Get modify_ip
     *
     * @return string
     */
    public function getModifyIp()
    {
        return $this->modify_ip;
    }

    /**
     * Set has_video
     *
     * @param boolean $hasVideo
     * @return Ad
     */
    public function setHasVideo($hasVideo)
    {
        $this->has_video = $hasVideo;

        return $this;
    }

    /**
     * Get has_video
     *
     * @return boolean
     */
    public function getHasVideo()
    {
        return $this->has_video;
    }

    /**
     * Set renewed_at
     *
     * @param integer $renewedAt
     * @return Ad
     */
    public function setRenewedAt($renewedAt)
    {
        $this->renewed_at = $renewedAt;

        return $this;
    }

    /**
     * Get renewed_at
     *
     * @return integer
     */
    public function getRenewedAt()
    {
        return $this->renewed_at;
    }

    /**
     * Set expires_at
     *
     * @param integer $expiresAt
     * @return Ad
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expires_at = $expiresAt;

        return $this;
    }

    /**
     * Get expires_at
     *
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set is feed_ad
     *
     * @param boolean $is_feed_ad
     * @return Ad
     */
    public function setIsFeedAd($is_feed_ad)
    {
        $this->is_feed_ad = $is_feed_ad;

        return $this;
    }

    /**
     * Get is_renewal_mail_sent
     *
     * @return boolean
     */
    public function getIsFeedAd()
    {
        return $this->is_feed_ad;
    }

    /**
     * Set is_renewal_mail_sent
     *
     * @param boolean $isRenewalMailSent
     * @return Ad
     */
    public function setIsRenewalMailSent($isRenewalMailSent)
    {
        $this->is_renewal_mail_sent = $isRenewalMailSent;

        return $this;
    }

    /**
     * Get is_renewal_mail_sent
     *
     * @return boolean
     */
    public function getIsRenewalMailSent()
    {
        return $this->is_renewal_mail_sent;
    }

    /**
     * Set renew_count
     *
     * @param integer $renewCount
     * @return Ad
     */
    public function setRenewCount($renewCount)
    {
        $this->renew_count = $renewCount;

        return $this;
    }

    /**
     * Get renew_count
     *
     * @return integer
     */
    public function getRenewCount()
    {
        return $this->renew_count;
    }

    /**
     * Set sold_at
     *
     * @param integer $soldAt
     * @return Ad
     */
    public function setSoldAt($soldAt)
    {
        $this->sold_at = $soldAt;

        return $this;
    }

    /**
     * Get sold_at
     *
     * @return integer
     */
    public function getSoldAt()
    {
        return $this->sold_at;
    }

    /**
     * Set sold_price
     *
     * @param float $soldPrice
     * @return Ad
     */
    public function setSoldPrice($soldPrice)
    {
        $this->sold_price = $soldPrice;

        return $this;
    }

    /**
     * Get sold_price
     *
     * @return float
     */
    public function getSoldPrice()
    {
        return $this->sold_price;
    }

    /**
     * Set created_at
     *
     * @param integer $createdAt
     * @return Ad
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
     * @return Ad
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
     * Set status
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $status
     * @return Ad
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
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return Ad
     */
    public function setCategory(\Fa\Bundle\EntityBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set type
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Entity $type
     * @return Ad
     */
    public function setType(\Fa\Bundle\EntityBundle\Entity\Entity $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Fa\Bundle\EntityBundle\Entity\Entity
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set personalized_title
     *
     * @param string $personalizedTitle
     * @return Ad
     */
    public function setPersonalizedTitle($personalizedTitle)
    {
        if ($personalizedTitle) {
            $personalizedTitle = strip_tags(htmlspecialchars_decode($personalizedTitle), '');
        }
        $this->personalized_title = $personalizedTitle;

        return $this;
    }

    /**
     * Get personalized_title
     *
     * @return string
     */
    public function getPersonalizedTitle()
    {
        $this->personalized_title = strip_tags(htmlspecialchars_decode($this->personalized_title), '');

        return $this->personalized_title;
    }

    /**
     * Set qty
     *
     * @param integer $qty
     * @return Ad
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return integer
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set qty_sold
     *
     * @param integer $qtySold
     * @return Ad
     */
    public function setQtySold($qtySold)
    {
        $this->qty_sold = $qtySold;

        return $this;
    }

    /**
     * Get qty_sold
     *
     * @return integer
     */
    public function getQtySold()
    {
        return $this->qty_sold;
    }

    /**
     * Add adLocation
     *
     * @param \Fa\Bundle\AdBundle\Entity\AdLocation $adLocation
     *
     * @return Ad
     */
    public function addAdLocation(\Fa\Bundle\AdBundle\Entity\AdLocation $adLocation)
    {
        $this->ad_locations[] = $adLocation;

        return $this;
    }

    /**
     * Remove adLocation
     *
     * @param \Fa\Bundle\AdBundle\Entity\AdLocation $adLocation
     */
    public function removeAdLocation(\Fa\Bundle\AdBundle\Entity\AdLocation $adLocation)
    {
        $this->ad_locations->removeElement($adLocation);
    }

    /**
     * Get adLocations
     *
     * @return AdLocation[]
     */
    public function getAdLocations()
    {
        return $this->ad_locations;
    }

    /**
     * Set use privacy number
     *
     * @param boolean $usePrivacyNumber
     * @return Ad
     */
    public function setUsePrivacyNumber($usePrivacyNumber)
    {
        $this->use_privacy_number = $usePrivacyNumber;

        return $this;
    }

    /**
     * Get use privacy number
     *
     * @return boolean
     */
    public function getUsePrivacyNumber()
    {
        return $this->use_privacy_number;
    }

    /**
     * Set privacy number
     *
     * @param string $privacyNumber
     * @return Ad
     */
    public function setPrivacyNumber($privacyNumber)
    {
        $this->privacy_number = $privacyNumber;

        return $this;
    }

    /**
     * Set trans_id
     *
     * @param string $trans_id
     * @return Ad
     */
    public function setTransId($trans_id)
    {
        $this->trans_id = $trans_id;

        return $this;
    }

    /**
     * Get privacy number
     *
     * @return string
     */
    public function getPrivacyNumber()
    {
        return $this->privacy_number;
    }

    /**
     * Get trans id
     *
     * @return string
     */
    public function getTransId()
    {
        return $this->trans_id;
    }

    /**
     * Set published_at
     *
     * @param integer $publishedAt
     * @return Ad
     */
    public function setPublishedAt($publishedAt)
    {
        $this->published_at = $publishedAt;

        return $this;
    }

    /**
     * Get published_at
     *
     * @return integer
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    /**
     * Set deliveryMethodOption
     *
     * @param \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption $deliveryMethodOption
     *
     * @return Cart
     */
    public function setDeliveryMethodOption(\Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption $deliveryMethodOption = null)
    {
        $this->delivery_method_option = $deliveryMethodOption;

        return $this;
    }

    /**
     * Get deliveryMethodOption
     *
     * @return \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption
     */
    public function getDeliveryMethodOption()
    {
        return $this->delivery_method_option;
    }

    /**
     * Get deliveryMethodOption id
     *
     * @return \Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption
     */
    public function getDeliveryMethodOptionId()
    {
        return $this->getDeliveryMethodOption() ? $this->getDeliveryMethodOption()->getId() : null;
    }

    /**
     * Get payment method
     *
     * @return integer
     */
    public function getPaymentMethodId()
    {
        return $this->payment_method_id;
    }

    /**
     * Set paymemt method id
     *
     * @param integer $paymentMethodId
     * @return Ad
     */
    public function setPaymentMethodId($paymentMethodId)
    {
        $this->payment_method_id = $paymentMethodId;

        return $this;
    }

    /**
     * get rejected reason
     *
     * @return string
     */
    public function getRejectedReason()
    {
        return $this->rejected_reason;
    }

    /**
     * set rejected reason
     *
     * @param string $rejected_reason
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function setRejectedReason($rejected_reason)
    {
        $this->rejected_reason = $rejected_reason;
        return $this;
    }

    /**
     * Set weekly_refresh_at
     *
     * @param integer $weeklyRefreshAt
     * @return Ad
     */
    public function setWeeklyRefreshAt($weeklyRefreshAt)
    {
        $this->weekly_refresh_at = $weeklyRefreshAt;

        return $this;
    }

    /**
     * Get weekly_refresh_at
     *
     * @return integer
     */
    public function getWeeklyRefreshAt()
    {
        return $this->weekly_refresh_at;
    }

    /**
     * Set use_email_response
     *
     * @param string $use_email_response
     * @return Ad
     */
    public function setUseEmailResponse($use_email_response)
    {
        $this->use_email_response = $use_email_response;

        return $this;
    }

    /**
     * Get use_email_response
     *
     * @return string
     */
    public function getUseEmailResponse()
    {
        return $this->use_email_response;
    }

    /**
     * Set use_map_link
     *
     * @param string $use_map_link
     * @return Ad
     */
    public function setUseMapLink($use_map_link)
    {
        $this->use_map_link = $use_map_link;

        return $this;
    }

    /**
     * Get use_map_link
     *
     * @return string
     */
    public function getUseMapLink()
    {
        return $this->use_map_link;
    }

    /**
     * Set ad_ref
     *
     * @param string $ad_ref
     * @return Ad
     */
    public function setAdRef($ad_ref)
    {
        $this->ad_ref = $ad_ref;

        return $this;
    }

    /**
     * Get ad_ref
     *
     * @return string
     */
    public function getAdRef()
    {
        return $this->ad_ref;
    }

    /**
     * Set old_status
     *
     * @param string $old_status
     * @return Ad
     */
    public function setOldStatus($old_status)
    {
        $this->old_status = $old_status;

        return $this;
    }

    /**
     * Get old_status
     *
     * @return string
     */
    public function getOldStatus()
    {
        return $this->old_status;
    }

    /**
     * Set tamsin_ad_ref
     *
     * @param string $tamsin_ad_ref
     * @return Ad
     */
    public function setTamsinAdRef($tamsin_ad_ref)
    {
        $this->tamsin_ad_ref = $tamsin_ad_ref;

        return $this;
    }

    /**
     * Get tamsin_ad_ref
     *
     * @return string
     */
    public function getTamsinAdRef()
    {
        return $this->tamsin_ad_ref;
    }

    //getIsFeedAd
    /**
     * Check ad has top ad upsell.
     *
     * @param object $container Container instance.
     *
     * @return boolean
     */
    public function isTopAd($container)
    {
        if ($container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsellCountByIdAndType($this->getId(), \Fa\Bundle\PromotionBundle\Repository\UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Check ad has highlight ad upsell.
     *
     * @param object $container Container instance.
     *
     * @return boolean
     */
    public function isUrgentAd($container)
    {
        if ($container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsellCountByIdAndType($this->getId(), \Fa\Bundle\PromotionBundle\Repository\UpsellRepository::UPSELL_TYPE_URGENT_ADVERT_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Check ad has home page featured.
     *
     * @param object $container Container instance.
     *
     * @return boolean
     */
    public function isHomeFeaturedAd($container)
    {
        if ($container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsellCountByIdAndType($this->getId(), \Fa\Bundle\PromotionBundle\Repository\UpsellRepository::UPSELL_TYPE_HOMEPAGE_FEATURE_ADVERT_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Check ad has weekly refresh ad upsell.
     *
     * @param object $container Container instance.
     *
     * @return boolean
     */
    public function isWeeklyRefreshAd($container)
    {
        if ($container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsellCountByIdAndType($this->getId(), \Fa\Bundle\PromotionBundle\Repository\UpsellRepository::UPSELL_TYPE_AD_REFRESH_ID)) {
            return true;
        }

        return false;
    }

    /**
     * Set is_trade_ad
     *
     * @param boolean $is_trade_ad
     * @return Ad
     */
    public function setIsTradeAd($is_trade_ad)
    {
        $this->is_trade_ad = $is_trade_ad;

        return $this;
    }

    /**
     * Get is_trade_ad
     *
     * @return boolean
     */
    public function getIsTradeAd()
    {
        return $this->is_trade_ad;
    }

    /**
     * Set old_cat_id
     *
     * @param integer $old_cat_id
     * @return Ad
     */
    public function setOldCatId($old_cat_id)
    {
        $this->old_cat_id = $old_cat_id;

        return $this;
    }

    /**
     * Get old_cat_id
     *
     * @return integer
     */
    public function getOldCatId()
    {
        return $this->old_cat_id;
    }

    /**
     * Set old_class_id
     *
     * @param integer $old_class_id
     * @return Ad
     */
    public function setOldClassId($old_class_id)
    {
        $this->old_class_id = $old_class_id;

        return $this;
    }

    /**
     * Get old_class_id
     *
     * @return integer
     */
    public function getOldClassId()
    {
        return $this->old_class_id;
    }

    /**
     * Set old_sub_class_id
     *
     * @param integer $old_sub_class_id
     * @return Ad
     */
    public function setOldSubClassId($old_sub_class_id)
    {
        $this->old_sub_class_id = $old_sub_class_id;

        return $this;
    }

    /**
     * Get old_sub_class_id
     *
     * @return integer
     */
    public function getOldSubClassId()
    {
        return $this->old_sub_class_id;
    }

    /**
     * Set update_type
     *
     * @param string $update_type
     * @return Ad
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
     * Set is paid_ad
     *
     * @param boolean $is_paid_ad
     * @return Ad
     */
    public function setIsPaidAd($is_paid_ad)
    {
        $this->is_paid_ad = $is_paid_ad;

        return $this;
    }

    /**
     * Get is_
     *
     * @return boolean
     */
    public function getIsPaidAd()
    {
        return $this->is_paid_ad;
    }

    /**
     * Set is manual_refresh
     *
     * @param boolean $manual_refresh
     * @return Ad
     */
    public function setManualRefresh($manual_refresh)
    {
        $this->manual_refresh  = $manual_refresh;

        return $this;
    }

    /**
     * Get manual_refresh
     *
     * @return boolean
     */
    public function getManualRefresh()
    {
        return $this->manual_refresh;
    }

    /**
     * Set is blocked_ad
     *
     * @param boolean $is_blocked_ad
     * @return Ad
     */
    public function setIsBlockedAd($is_blocked_ad)
    {
        $this->is_blocked_ad = $is_blocked_ad;

        return $this;
    }

    /**
     * Get is_blocked_ad
     *
     * @return boolean
     */
    public function getIsBlockedAd()
    {
        return $this->is_blocked_ad;
    }

    /**
     * Get price_old_text
     *
     * @return integer
     */
    public function getPriceOldText()
    {
        return $this->price_old_text;
    }

    /**
     * Set price_old_text
     *
     * @param integer $updatedAt
     * @return Ad
     */
    public function setPriceOldText($price_old_text)
    {
        $this->price_old_text = $price_old_text;

        return $this;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Ad
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
     * Set email
     *
     * @param string $email
     * @return Ad
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
     * Set skip_solr
     *
     * @param string $skip_solr
     * @return Ad
     */
    public function setSkipSolr($skip_solr)
    {
        $this->skip_solr = $skip_solr;

        return $this;
    }

    /**
     * Get skip_solr
     *
     * @return string
     */
    public function getSkipSolr()
    {
        return $this->skip_solr;
    }

    /**
     * Set edited_at.
     *
     * @param string $edited_at
     * @return Ad
     */
    public function setEditedAt($edited_at)
    {
        $this->edited_at = $edited_at;

        return $this;
    }

    /**
     * Get edited_at.
     *
     * @return string
     */
    public function getEditedAt()
    {
        return $this->edited_at;
    }

    /**
     * Set source.
     *
     * @param string $source
     * @return Ad
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set track_back_url.
     *
     * @param string $track_back_url
     * @return Ad
     */
    public function setTrackBackUrl($track_back_url)
    {
        $this->track_back_url = $track_back_url;

        return $this;
    }

    /**
     * Get track_back_url.
     *
     * @return string
     */
    public function getTrackBackUrl()
    {
        return $this->track_back_url;
    }

    /**
     * Set qty_sold_total.
     *
     * @param integer $qty_sold_total
     * @return Ad
     */
    public function setQtySoldTotal($qty_sold_total)
    {
        $this->qty_sold_total = $qty_sold_total;

        return $this;
    }

    /**
     * Get qty_sold_total.
     *
     * @return integer
     */
    public function getQtySoldTotal()
    {
        return $this->qty_sold_total;
    }

    /**
     * Set admin_user_id.
     *
     * @param integer $admin_user_id
     * @return Ad
     */
    public function setAdminUserId($admin_user_id)
    {
        $this->admin_user_id = $admin_user_id;

        return $this;
    }

    /**
     * Get admin_user_id.
     *
     * @return integer
     */
    public function getAdminUserId()
    {
        return $this->admin_user_id;
    }

    /**
     * Set original_created_at.
     *
     * @param integer $original_created_at
     * @return Ad
     */
    public function setOriginalCreatedAt($original_created_at)
    {
        $this->original_created_at = $original_created_at;

        return $this;
    }

    /**
     * Get original_created_at.
     *
     * @return integer
     */
    public function getOriginalCreatedAt()
    {
        return $this->original_created_at;
    }

    /**
     * Set source_latest.
     *
     * @param string $source_latest
     * @return Ad
     */
    public function setSourceLatest($source_latest)
    {
        $this->source_latest = $source_latest;

        return $this;
    }

    /**
     * Get source_latest.
     *
     * @return string
     */
    public function getSourceLatest()
    {
        return $this->source_latest;
    }

    /**
     * Set future_publish_at.
     *
     * @param string $future_publish_at
     * @return Ad
     */
    public function setFuturePublishAt($future_publish_at)
    {
        $this->future_publish_at = $future_publish_at;

        return $this;
    }

    /**
     * Get future_publish_at.
     *
     * @return string
     */
    public function getFuturePublishAt()
    {
        return $this->future_publish_at;
    }

    /**
     * Set old_future_publish_at.
     *
     * @param string $old_future_publish_at
     * @return Ad
     */
    public function setOldFuturePublishAt($old_future_publish_at)
    {
        $this->old_future_publish_at = $old_future_publish_at;

        return $this;
    }

    /**
     * Get old_future_publish_at.
     *
     * @return string
     */
    public function getOldFuturePublishAt()
    {
        return $this->old_future_publish_at;
    }

    /**
     * Set business_phone.
     *
     * @param string $business_phone
     * @return Ad
     */
    public function setBusinessPhone($business_phone)
    {
        $this->business_phone = $business_phone;

        return $this;
    }

    /**
     * Get business_phone.
     *
     * @return string
     */
    public function getBusinessPhone()
    {
        return $this->business_phone;
    }

    /**
     * Set ad_edit_moderated_at.
     *
     * @param integer $ad_edit_moderated_at
     * @return Ad
     */
    public function setAdEditModeratedAt($ad_edit_moderated_at)
    {
        $this->ad_edit_moderated_at = $ad_edit_moderated_at;

        return $this;
    }

    /**
     * Get ad_edit_moderated_at.
     *
     * @return integer
     */
    public function getAdEditModeratedAt()
    {
        return $this->ad_edit_moderated_at;
    }

    /**
     * Set youtube_video_url.
     *
     * @param string $youtube_video_url
     * @return Ad
     */
    public function setYoutubeVideoUrl($youtube_video_url)
    {
        $this->youtube_video_url = $youtube_video_url;

        return $this;
    }

    /**
     * Get youtube_video_url.
     *
     * @return string
     */
    public function getYoutubeVideoUrl()
    {
        return $this->youtube_video_url;
    }

    /**
     * Set is_add_photo_mail_sent.
     *
     * @param string $is_add_photo_mail_sent
     * @return Ad
     */
    public function setIsAddPhotoMailSent($is_add_photo_mail_sent)
    {
        $this->is_add_photo_mail_sent = $is_add_photo_mail_sent;

        return $this;
    }

    /**
     * Get is_add_photo_mail_sent.
     *
     * @return string
     */
    public function getIsAddPhotoMailSent()
    {
        return $this->is_add_photo_mail_sent;
    }

    /**
     * Set original_published_at.
     *
     * @param string $original_published_at
     * @return Ad
     */
    public function setOriginalPublishedAt($original_published_at)
    {
        $this->original_published_at = $original_published_at;

        return $this;
    }

    /**
     * Get original_published_at.
     *
     * @return string
     */
    public function getOriginalPublishedAt()
    {
        return $this->original_published_at;
    }

    /**
     * Set ti_ad_id.
     *
     * @param string $ti_ad_id
     * @return Ad
     */
    public function setTiAdId($ti_ad_id)
    {
        $this->ti_ad_id = $ti_ad_id;

        return $this;
    }

    /**
     * Get ti_ad_id.
     *
     * @return string
     */
    public function getTiAdId()
    {
        return $this->ti_ad_id;
    }

    /**
     * Set campaign
     *
     * @param \Fa\Bundle\AdBundle\Entity\Campaigns $campaign
     * @return Ad
     */
    public function setCampaign(\Fa\Bundle\AdBundle\Entity\Campaigns $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \Fa\Bundle\AdBundle\Entity\Campaigns
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set isBoosted
     *
     * @param string $isBoosted
     * @return Ad
     */
    public function setIsBoosted($is_boosted = null)
    {
        $this->is_boosted = $is_boosted;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getIsBoosted()
    {
        return $this->is_boosted;
    }

    /**
     * Set boosted_at
     *
     * @param integer $boosted_at
     * @return Ad
     */
    public function setBoostedAt()
    {
        $this->boosted_at = time();

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return integer
     */
    public function getBoostedAt()
    {
        return $this->boosted_at;
    }
}
