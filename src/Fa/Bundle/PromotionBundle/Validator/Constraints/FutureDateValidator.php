<?php

namespace Fa\Bundle\PromotionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use DateTime;

class FutureDateValidator extends ConstraintValidator
{
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }

    public function validate($value, Constraint $constraint)
    {
        // Format date
        $date = new DateTime(str_replace('/', '-', $value));
        $today_date = new DateTime(date('d-m-Y', strtotime('yesterday')));
        if ($date <= $today_date) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
