<?php

/**
 * This file is part of the fa entity bundle.
 *
 * @copyright Copyright (c) 2017, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Fa\Bundle\EntityBundle\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;
use Fa\Bundle\PromotionBundle\Repository\CategoryUpsellRepository;

/**
 * This table is used to store category information.
 *
 * @author     Akash M. Pai <akash.pai@fridaymediagroup.com>
 * @copyright  2017 Friday Media Group Ltd
 * @version    v1.0
 *
 * @ORM\Table(name="category_upsell")
 * @ORM\Entity(repositoryClass="Fa\Bundle\PromotionBundle\Repository\CategoryUpsellRepository")
 * @Gedmo\Loggable(logEntryClass="Fa\Bundle\EntityBundle\Entity\FaEntityLog")
 */
class CategoryUpsell
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=FALSE)
     */
    protected $category;//, inversedBy="upsells", cascade={"persist"}//, onDelete="CASCADE"

    /**
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\EntityBundle\Entity\Category")
     * @ORM\JoinColumn(name="root_category_id", referencedColumnName="id", nullable=FALSE)
     */
    protected $root_category;//, inversedBy="upsells", cascade={"persist"}//, onDelete="CASCADE"

    /**
     * @var Upsell
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\Upsell")
     * @ORM\JoinColumn(name="upsell_id", referencedColumnName="id", nullable=FALSE)
     */
    protected $upsell;//, inversedBy="categoryUpsells", cascade={"persist"}//, onDelete="CASCADE"

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=15, scale=2, nullable=true)
     * @Assert\Length(max = 12, maxMessage = "Price cannot be longer than {{ limit }} digits long.")
     * @Gedmo\Versioned
     */
    private $price;

    /**
     *
     * @var boolean @ORM\Column(name="show_in_filters", type="boolean", nullable=true, options={"default" : 0})
     */
    private $show_in_filters;

    /**
     *
     * @var string @ORM\Column(name="status", type="boolean", nullable=true, options={"default" : "1"})
     */
    private $status;

    /**
     * @var float @ORM\Column(name="min_price", type="float", nullable=true)
     */
    private $min_price;

    /**
     * @var float @ORM\Column(name="max_price", type="float", nullable=true)
     */
    private $max_price;

    /**
     * Constructor
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
     * Set price
     *
     * @param float $price
     * @return $this
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

    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     * @return $this
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRootCategory()
    {
        return $this->root_category;
    }

    /**
     * @param Category|null $rootCategory
     * @return $this
     */
    public function setRootCategory(Category $rootCategory = null)
    {
        $this->root_category = $rootCategory;
        return $this;
    }

    /**
     * @return Upsell
     */
    public function getUpsell()
    {
        return $this->upsell;
    }

    /**
     * @param Upsell|null $upsell
     * @return $this
     */
    public function setUpsell(Upsell $upsell = null)
    {
        $this->upsell = $upsell;
        return $this;
    }

    /**
     * getter for show_in_filters value for an upsell entry
     *
     * @return bool
     */
    public function getShowInFilters()
    {
        return $this->show_in_filters;
    }

    /**
     * setter for show_in_filters for an upsell entry
     *
     * @param $showInFilters
     * @return $this
     */
    public function setShowInFilters($showInFilters)
    {
        $this->show_in_filters = $showInFilters;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return float
     */
    public function getMinPrice()
    {
        return $this->min_price;
    }

    /**
     * @param $price
     * @return $this
     */
    public function setMinPrice($price)
    {
        $this->min_price = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getMaxPrice()
    {
        return $this->max_price;
    }

    /**
     * @param $price
     * @return $this
     */
    public function setMaxPrice($price)
    {
        $this->max_price = $price;

        return $this;
    }

}
