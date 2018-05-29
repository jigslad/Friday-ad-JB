<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Fa\Bundle\AdBundle\Entity\SearchKeyword
 *
 * This table is used to store search keywords information.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="search_keyword")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\SearchKeywordRepository")
 * @UniqueEntity(fields="keyword", message="This keyword is already exist in our system.")
 * @ORM\HasLifecycleCallbacks
 */
class SearchKeyword
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
     * @ORM\Column(name="do_not_overwrite_category", type="boolean", nullable=true, options={"default" = 0})
     */
    private $do_not_overwrite_category = 0;

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
     * @return SearchKeyword
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
     * Set search_count.
     *
     * @param integer $search_count
     * @return SearchKeyword
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
     * @return SearchKeyword
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
     * @return SearchKeyword
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
     * Set do_not_overwrite_category.
     *
     * @param boolean $do_not_overwrite_category
     * @return SearchKeyword
     */
    public function setDoNotOverwriteCategory($do_not_overwrite_category)
    {
        $this->do_not_overwrite_category = $do_not_overwrite_category;

        return $this;
    }

    /**
     * Get do_not_overwrite_category.
     *
     * @return boolean
     */
    public function getDoNotOverwriteCategory()
    {
        return $this->do_not_overwrite_category;
    }

    /**
     * Set is_updated.
     *
     * @param boolean $is_updated
     * @return SearchKeyword
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
