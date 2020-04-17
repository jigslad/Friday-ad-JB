<?php

namespace Fa\Bundle\PromotionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FutureDate extends Constraint
{
    public $message = 'This "{{ string }}" should greater then today date ';
}
