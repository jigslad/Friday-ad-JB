<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store information about seo tool popular keywords.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="category_recommended_slot")
 * @ORM\Entity(repositoryClass="Fa\Bundle\EntityBundle\Repository\CategoryRecommendedSlotRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\EntityBundle\Listener\CategoryRecommendedSlotListener" })
 * @ORM\HasLifecycleCallbacks
 */
class CategoryRecommendedSlot
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
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_title", type="string", length=500, nullable=false)
     */
    private $sub_title;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /** @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", length=10, nullable=false)
     */
    private $user_id;

    /**
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * })
     */
    private $category;

    /**
     * Constructor.
     */
    public function __construct()
    {
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
     * Set created at.
     *
     * @param integer $createdAt
     *
     * @return StaticPage
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created at.
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->created_at;
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
     * @return \Fa\Bundle\EntityBundle\Entity\Category $category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set title.
     *
     * @param string $title
     * @return CategoryRecommendedSlot
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set url.
     *
     * @param string $url
     * @return CategoryRecommendedSlot
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set sub_title.
     *
     * @param string $sub_title
     * @return CategoryRecommendedSlot
     */
    public function setSubTitle($sub_title)
    {
        $this->sub_title = $sub_title;

        return $this;
    }

    /**
     * Get sub_title.
     *
     * @return string
     */
    public function getSubTitle()
    {
        return $this->sub_title;
    }

    /**
     * Set user_id.
     *
     * @param integer $user_id
     * @return CategoryRecommendedSlot
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user_id.
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }
}
