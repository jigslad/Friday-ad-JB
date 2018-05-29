<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * FaPhone custom constraint.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

/**
 * @Annotation
 */
class FaPhone extends Constraint
{
    public $message = 'Phone number is not a valid.';
}
