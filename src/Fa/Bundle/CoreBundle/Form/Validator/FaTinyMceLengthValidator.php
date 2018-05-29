<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LengthValidator;

/**
 * FaTinyMceLengthValidator custom constraint validator.
 *
 * @author Samir Amrutya<samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FaTinyMceLengthValidator extends LengthValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $value = preg_replace('/<p>&nbsp;<\/p>/', '', $value);
            $value = preg_replace('/<[^>]*>/i', '', $value);
            $value = preg_replace('/\r|\n|\r\n/i', '', $value);
            $value = html_entity_decode($value);
        }

        return parent::validate($value, $constraint);
    }
}
