<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fa\Bundle\AdBundle\Entity\AdUserPackageUpsell
 *
 * This table is used to store ad image information.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="ad_user_package_upsell", indexes={@ORM\Index(name="fa_ad_id_index", columns={"ad_id"}) })
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\AdUserPackageUpsellRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AdUserPackageUpsell
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
     * @ORM\Column(name="value", type="string", length=20, nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="value1", type="string", length=20, nullable=true)
     */
    private $value1;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_at", type="integer", length=10, nullable=true)
     */
    private $expires_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="started_at", type="integer", length=10, nullable=true)
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
     * @var \Fa\Bundle\PromotionBundle\Entity\Upsell
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Upsell")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="upsell_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * })
     */
    private $upsell;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\AdUserPackage
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\AdUserPackage")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_user_package_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $ad_user_package;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", nullable=true, options={"default" = 0})
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
     * Set value
     *
     * @param string $value
     *
     * @return AdUserPackageUpsell
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
     * Set value1
     *
     * @param string $value1
     *
     * @return AdUserPackageUpsell
     */
    public function setValue1($value1)
    {
        $this->value1 = $value1;

        return $this;
    }

    /**
     * Get value1
     *
     * @return string
     */
    public function getValue1()
    {
        return $this->value1;
    }

    /**
     * Set expiresAt
     *
     * @param integer $expiresAt
     *
     * @return AdUserPackageUpsell
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
     * @return AdUserPackageUpsell
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
     * @return AdUserPackageUpsell
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
     * @param boolean $status
     *
     * @return AdUserPackageUpsell
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
     * Set upsell
     *
     * @param \Fa\Bundle\PromotionBundle\Entity\Upsell $upsell
     *
     * @return AdUserPackageUpsell
     */
    public function setUpsell(\Fa\Bundle\PromotionBundle\Entity\Upsell $upsell = null)
    {
        $this->upsell = $upsell;

        return $this;
    }

    /**
     * Get upsell
     *
     * @return \Fa\Bundle\PromotionBundle\Entity\Upsell
     */
    public function getUpsell()
    {
        return $this->upsell;
    }

    /**
     * Set adUserPackage
     *
     * @param \Fa\Bundle\AdBundle\Entity\AdUserPackage $adUserPackage
     *
     * @return AdUserPackageUpsell
     */
    public function setAdUserPackage(\Fa\Bundle\AdBundle\Entity\AdUserPackage $adUserPackage = null)
    {
        $this->ad_user_package = $adUserPackage;

        return $this;
    }

    /**
     * Get adUserPackage
     *
     * @return \Fa\Bundle\AdBundle\Entity\AdUserPackage
     */
    public function getAdUserPackage()
    {
        return $this->ad_user_package;
    }

    /**
     * Set duration
     *
     * @param string duration
     * @return Upsell
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
}
