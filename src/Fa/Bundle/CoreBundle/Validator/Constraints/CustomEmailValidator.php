<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fa\Bundle\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * This validator is used to validate email.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version 1.0
 *
 */
class CustomEmailValidator extends ConstraintValidator
{

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Validator\ConstraintValidatorInterface::validate()
     *
     * @param object $object
     * @param object $constraint Constraint object.
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!preg_match('/^[_a-zA-Z0-9-+]+(\.[_a-zA-Z0-9-+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,})$/', $value)) {
            $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();

            return;
        }
    }
}
