<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;

/**
 * Verifies that a string is parseable as Twig.
 *
 * Uses a dedicated lightweight environment (no extensions, no loader hooks) so
 * untrusted input cannot exercise the live environment's compiler cache.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Validator\Constraints\ValidTwigValidatorTest
 */
final class ValidTwigValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ValidTwig) {
            throw new UnexpectedTypeException($constraint, ValidTwig::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (! \is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $environment = new Environment(new ArrayLoader());

        try {
            $environment->parse($environment->tokenize(new Source($value, 'validator/template')));
        } catch (SyntaxError $error) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ error }}', $error->getRawMessage())
                ->setCode('valid-twig')
                ->addViolation();
        }
    }
}
