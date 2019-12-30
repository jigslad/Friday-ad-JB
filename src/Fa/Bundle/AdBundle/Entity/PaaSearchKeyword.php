<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * Fa\Bundle\AdBundle\Entity\PaaSearchKeyword
 *
 * This table is used to store Paa search keywords information.
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="paa_search_keyword")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PaaSearchKeywordRepository")
 * @UniqueEntity(fields="keyword", message="This keyword is already exist in our system.")
 * @ORM\HasLifecycleCallbacks
 */
class PaaSearchKeyword
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
     * @var \Fa\Bundle\EntityBundle\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * })
     */
     private $category;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="Keyword is required.")
     * @ORM\Column(name="keyword", type="string", length=255)
     */
    private $keyword;

    /**
     * @var integer
     *
     * @Assert\NotBlank(message="Monthly searches is required.")
     * @ORM\Column(name="search_count", type="integer", length=10, options={"default" = 0})
     */
    private $search_count = 0;

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
     * @var boolean
     *
     * @ORM\Column(name="is_updated", type="boolean", nullable=true, options={"default" = 0})
     */
    private $is_updated = 0;

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->created_at = time();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = time();
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
     * Set keyword.
     *
     * @param string $keyword
     * @return PaaSearchKeyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Set category
     *
     * @param \Fa\Bundle\EntityBundle\Entity\Category $category
     * @return PaaSearchKeyword
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
     * Set search_count.
     *
     * @param integer $search_count
     * @return PaaSearchKeyword
     */
    public function setSearchCount($search_count)
    {
        $this->search_count = $search_count;

        return $this;
    }

    /**
     * Get search_count.
     *
     * @return integer
     */
    public function getSearchCount()
    {
        return $this->search_count;
    }

    /**
     * Set created_at.
     *
     * @param string $created_at
     * @return PaaSearchKeyword
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Get created_at.
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param integer $updatedAt
     * @return PaaSearchKeyword
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
     * Set is_updated.
     *
     * @param boolean $is_updated
     * @return PaaSearchKeyword
     */
    public function setIsUpdated($is_updated)
    {
        $this->is_updated = $is_updated;

        return $this;
    }

    /**
     * Get is_updated.
     *
     * @return boolean
     */
    public function getIsUpdated()
    {
        return $this->is_updated;
    }
}
