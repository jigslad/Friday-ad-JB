<?php

namespace Fa\Bundle\AdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fa\Bundle\AdBundle\Entity\PaaSearchKeywordCategory
 *
 * This table is used to store processed Paa search keywords with category information.
 *
 * @author Samir Amrutya <jigar.lad@fridaymediagroup.com>
 * @copyright  2014 Friday Media Group Ltd.
 *
 * @ORM\Table(name="paa_search_keyword_category")
 * @ORM\Entity(repositoryClass="Fa\Bundle\AdBundle\Repository\PaaSearchKeywordCategoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaaSearchKeywordCategory
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
     * @var integer
     *
     * @ORM\Column(name="paa_search_keyword_id", type="integer", length=10)
     */
    private $search_keyword_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", length=10, nullable=true)
     */
    private $category_id;

    /**
     * @var string
     *
     * @ORM\Column(name="keyword", type="string", length=255)
     */
    private $keyword;

    /**
     * @var integer
     *
     * @ORM\Column(name="search_count", type="integer", length=10, nullable=true, options={"default" = 0})
     */
    private $search_count = 0;

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
     * Set search_keyword_id.
     *
     * @param integer $search_keyword_id
     * @return SearchKeywordCategory
     */
    public function setSearchKeywordId($search_keyword_id)
    {
        $this->search_keyword_id = $search_keyword_id;

        return $this;
    }

    /**
     * Get search_keyword_id.
     *
     * @return integer
     */
    public function getSearchKeywordId()
    {
        return $this->search_keyword_id;
    }

    /**
     * Set category_id.
     *
     * @param integer $category_id
     * @return SearchKeywordCategory
     */
    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;

        return $this;
    }

    /**
     * Get category_id.
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set keyword.
     *
     * @param string $keyword
     * @return SearchKeywordCategory
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
     * @return SearchKeywordCategory
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
}
