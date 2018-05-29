<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fa\Bundle\AdBundle\Entity\AdUserPackage
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_user_package", indexes={@ORM\Index(name="fa_ad_id_index", columns={"ad_id"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdUserPackageRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class AdUserPackage
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
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $expires_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="started_at", type="integer", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $started_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\AdMain
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\AdMain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_main_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $ad_main;

    /**
     * @var integer
     *
     * @ORM\Column(name="ad_id", type="integer", nullable=true)
     */
    private $ad_id;

    /**
     * @var \Fa\Bundle\PromotionBundle\Entity\Package
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Package")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     * @Gedmo\Versioned
     */
    private $package;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
    private $user;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="smallint", options={"default" = 0})
     * @Gedmo\Versioned
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=10, nullable=true)
     * @Assert\Regex(pattern="/^[a-z0-9 ]+$/i", message="The value {{ value }} is not a valid alpha numeric.")
     * @Assert\Length(max = 8, maxMessage = "Duration cannot be longer than {{ limit }} characters long.")
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="ti_package", type="string", length=255, nullable=true)
     */
    private $ti_package;

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
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
     * Set price
     *
     * @param float $price
     *
     * @return AdUserPackage
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
     * Set expiresAt
     *
     * @param integer $expiresAt
     *
     * @return AdUserPackage
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expires_at = $expiresAt;

        return $this;
    }

    /**
     * Get expiresAt
     *
     * @return integer
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Set startedAt
     *
     * @param integer $startedAt
     *
     * @return AdUserPackage
     */
    public function setStartedAt($startedAt)
    {
        $this->started_at = $startedAt;

        return $this;
    }

    /**
     * Get startedAt
     *
     * @return integer
     */
    public function getStartedAt()
    {
        return $this->started_at;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return AdUserPackage
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return AdUserPackage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\AdMain $adMain
     *
     * @return AdUserPackageUpsell
     */
    public function setAdMain(\Fa\Bundle\AdBundle\Entity\AdMain $adMain = null)
    {
        $this->ad_main = $adMain;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdMain
     */
    public function getAdMain()
    {
        return $this->ad_main;
    }

    /**
     * Set package
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Package $package
     *
     * @return AdUserPackage
     */
    public function setPackage(\Fa\Bundle\PromotionBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Fa\Bundle\PromotionBundle\Entity\Package
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     *
     * @return AdUserPackage
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
     * Set duration
     *
     * @param string duration
     * @return Package
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Transaction
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set ad_id.
     *
     * @param integer $ad_id
     * @return DotmailerInfo
     */
    public function setAdId($ad_id)
    {
        $this->ad_id = $ad_id;

        return $this;
    }

    /**
     * Get category_id.
     *
     * @return integer
     */
    public function getAdId()
    {
        return $this->ad_id;
    }

    /**
     * Set ti_package.
     *
     * @param string $ti_package
     * @return AdUserPackage
     */
    public function setTiPackage($ti_package)
    {
        $this->ti_package = $ti_package;

        return $this;
    }

    /**
     * Get ti_package.
     *
     * @return string
     */
    public function getTiPackage()
    {
        return $this->ti_package;
    }
}
