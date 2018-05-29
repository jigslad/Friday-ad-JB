<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store information about landing page popular keywords.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="landing_page_popular_search")
 * @ORM\Entity(repositoryClass="Fa\Bundle\ContentBundle\Repository\LandingPagePopularSearchRepository")
 * @ORM\EntityListeners({ "Fa\Bundle\ContentBundle\Listener\LandingPagePopularSearchListener" })
 * @ORM\HasLifecycleCallbacks
 */
class LandingPagePopularSearch
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
     * @ORM\Column(name="url", type="text", nullable=false)
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
     * @ORM\Column(name="updated_at", type="integer", length=10, nullable=true)
     */
    private $updated_at;

    /**
     * @var \Fa\Bundle\ContentBundle\Entity\LandingPage
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ContentBundle\Entity\LandingPage", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="landing_page_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * })
     */
    private $landing_page;

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
     * Set updated at.
     *
     * @param integer $updatedAt
     *
     * @return StaticPage
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
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
     * Set landing_page
     *
     * @param \Fa\Bundle\ContentBundle\Entity\LandingPage $landingPage
     * @return Ad
     */
    public function setLandingPage(\Fa\Bundle\ContentBundle\Entity\LandingPage $landingPage = null)
    {
        $this->landing_page = $landingPage;

        return $this;
    }

    /**
     * Get landing_page
     *
     * @return \Fa\Bundle\ContentBundle\Entity\LandingPage $landingPage
     */
    public function getLandingPage()
    {
        return $this->landing_page;
    }

    /**
     * Set title.
     *
     * @param string $title
     * @return LandingPagePopularSearch
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
     * @return LandingPagePopularSearch
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
}
