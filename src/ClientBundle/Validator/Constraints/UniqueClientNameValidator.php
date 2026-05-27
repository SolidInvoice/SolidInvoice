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

namespace SolidInvoice\ClientBundle\Validator\Constraints;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use function is_string;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Validator\Constraints\UniqueClientNameValidatorTest
 */
final class UniqueClientNameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueClientName) {
            throw new UnexpectedTypeException($constraint, UniqueClientName::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ($this->clientRepository->findOneByNameIncludingArchived($value) instanceof Client) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
