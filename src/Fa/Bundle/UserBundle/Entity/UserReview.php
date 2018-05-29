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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * This table is used to store user review information.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="user_review")
 * @ORM\Entity(repositoryClass="Fa\Bundle\UserBundle\Repository\UserReviewRepository")
 * @Gedmo\Tree(type="nested")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({ "Fa\Bundle\UserBundle\Listener\UserReviewListener" })
 */
class UserReview
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
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="smallint", nullable=true)
     */
    private $rating;

    /**
     * @var \Fa\Bundle\AdBundle\Entity\Ad
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\AdBundle\Entity\Ad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ad_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $ad;

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
     * @var \Fa\Bundle\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="reviewer_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $reviewer;

    /**
     * @var string
     *
     * @ORM\Column(name="report", type="text", nullable=true)
     */
    private $report;

    /**
     * @var integer
     *
     * @ORM\Column(name="lft", type="integer")
     * @Gedmo\TreeLeft
     */
    private $lft;

    /**
     * @var integer
     *
     * @ORM\Column(name="rgt", type="integer")
     * @Gedmo\TreeRight
     */
    private $rgt;

    /**
     * @var integer
     *
     * @ORM\Column(name="root", type="integer")
     * @Gedmo\TreeRoot
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="lvl", type="integer")
     * @Gedmo\TreeLevel
     */
    private $lvl;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Fa\Bundle\UserBundle\Entity\UserReview", mappedBy="parent")
     * @ORM\OrderBy({
     *     "lft"="ASC"
     * })
     */
    private $children;

    /**
     * @var \Fa\Bundle\UserBundle\Entity\UserReview
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\UserBundle\Entity\UserReview", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @Gedmo\TreeParent
     */
    private $parent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="smallint", nullable=true, options={"default" = 0})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=100, nullable=true)
     */
    private $ip_address;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_seller", type="smallint", nullable=true, options={"default" = 0})
     */
    private $is_seller;

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
     * @ORM\Column(name="user_review_ad_id", type="integer")
     */
    private $user_review_ad_id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param string $id
     * @return UserReview
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
     * Set message
     *
     * @param string $message
     * @return UserReview
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set report
     *
     * @param string $report
     * @return UserReview
     */
    public function setReport($report)
    {
        $this->report = $report;

        return $this;
    }

    /**
     * Get report
     *
     * @return string
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Set rating
     *
     * @param string $rating
     * @return UserReview
     */
    public function setRating($rating)
    {
        $this->rating= $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return string
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return UserReview
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return UserReview
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return UserReview
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return UserReview
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Add children
     *
     * @param \Fa\Bundle\UserBundle\Entity\UserReview $children
     * @return UserReview
     */
    public function addChild(\Fa\Bundle\UserBundle\Entity\UserReview $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Fa\Bundle\UserBundle\Entity\UserReview $children
     */
    public function removeChild(\Fa\Bundle\UserBundle\Entity\UserReview $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Fa\Bundle\UserBundle\Entity\UserReview $parent
     * @return UserReview
     */
    public function setParent(\Fa\Bundle\UserBundle\Entity\UserReview $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Fa\Bundle\UserBundle\Entity\UserReview
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return AdTempImage
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
     * Set value.
     *
     * @param string $value
     * @return UserReview
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
     * Set ip_address.
     *
     * @param string $ip_address
     * @return UserReview
     */
    public function setIpAddress($ip_address)
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get ip_address.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }


    /**
     * Set ad
     *
     * @param \Fa\Bundle\AdBundle\Entity\Ad $ad
     * @return UserReview
     */
    public function setAd(\Fa\Bundle\AdBundle\Entity\Ad $ad = null)
    {
        $this->ad = $ad;

        return $this;
    }

    /**
     * Get ad
     *
     * @return \Fa\Bundle\AdBundle\Entity\Ad
     */
    public function getAd()
    {
        return $this->ad;
    }

    /**
     * Set user
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $user
     * @return UserReview
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
     * Set reviewer
     *
     * @param \Fa\Bundle\UserBundle\Entity\User $reviewer
     * @return UserReview
     */
    public function setReviewer(\Fa\Bundle\UserBundle\Entity\User $reviewer = null)
    {
        $this->reviewer = $reviewer;

        return $this;
    }

    /**
     * Get reviewer
     *
     * @return \Fa\Bundle\UserBundle\Entity\User
     */
    public function getReviewer()
    {
        return $this->reviewer;
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
     * Set title
     *
     * @param string $title
     * @return UserReview
     */
    public function setTitle($title)
    {
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
        return $this->title;
    }

    /**
     * Get message
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function __toString()
    {
        return $this->getMessage();
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
     * Set is_seller.
     *
     * @param boolean $is_seller
     * @return UserReview
     */
    public function setIsSeller($is_seller)
    {
        $this->is_seller = $is_seller;

        return $this;
    }

    /**
     * Get is_seller.
     *
     * @return boolean
     */
    public function getIsSeller()
    {
        return $this->is_seller;
    }


    /**
     * Set user_review_ad_id.
     *
     * @param string $user_review_ad_id
     * @return UserReview
     */
    public function setUserReviewAdId($user_review_ad_id)
    {
        $this->user_review_ad_id = $user_review_ad_id;

        return $this;
    }

    /**
     * Get user_review_ad_id.
     *
     * @return string
     */
    public function getUserReviewAdId()
    {
        return $this->user_review_ad_id;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return Message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }
}
