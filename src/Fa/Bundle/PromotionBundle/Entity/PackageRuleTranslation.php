<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * This table is used to package translation.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 *
 * @ORM\Table(name="package_rule_translation")
 * @ORM\Entity
 */
class PackageRuleTranslation extends AbstractPersonalTranslation
{
    /**
     * @var \Fa\Bundle\PromotionBundle\Entity\PackageRule
     *
     * @ORM\ManyToOne(targetEntity="Fa\Bundle\PromotionBundle\Entity\PackageRule", inversedBy="translations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $object;
}
