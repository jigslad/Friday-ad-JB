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
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * This table is used to store entity translation.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="static_page_translation")
 * @ORM\Entity
 */
class StaticPageTranslation extends AbstractPersonalTranslation
{
    /**
     * Object.
     *
     * @var \Fa\Bundle\ContentBundle\Entity\StaticPage
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\ContentBundle\Entity\StaticPage", inversedBy="translations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $object;
}
