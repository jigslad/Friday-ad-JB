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

use Symfony\Component\Validator\Constraints\RangeValidator;
use Symfony\Component\Validator\Constraint;

/**
 * FaRangeValidator custom constraint validator.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FaRangeValidator extends RangeValidator
{
    /**
     * Validate.
     *
     * @param $value
     * @param $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $value = str_replace(',', '', $value);
        }

        return parent::validate($value, $constraint);
    }
}
