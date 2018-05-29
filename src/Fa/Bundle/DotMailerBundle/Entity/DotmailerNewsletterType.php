<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This table is used to store information related to dotmailer newsletter type.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="dotmailer_newsletter_type")
 * @ORM\Entity(repositoryClass="Fa\Bundle\DotMailerBundle\Repository\DotmailerNewsletterTypeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DotmailerNewsletterType
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
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="ord", type="smallint", length=4)
     */
    private $ord;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true, options={"default" = 0})
     */
    private $parent_id = 0;

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return Message
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set name.
     *
     * @param string $name
     * @return DotmailerNewsletterType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set category_id.
     *
     * @param integer $category_id
     * @return DotmailerInfo
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
     * Set label.
     *
     * @param string $label
     * @return DotmailerNewsletterType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set ord.
     *
     * @param integer $ord
     * @return DotmailerNewsletterType
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;

        return $this;
    }

    /**
     * Get ord.
     *
     * @return integer
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set parent_id.
     *
     * @param string $parent_id
     * @return DotmailerNewsletterType
     */
    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;

        return $this;
    }

    /**
     * Get parent_id.
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parent_id;
    }
}
