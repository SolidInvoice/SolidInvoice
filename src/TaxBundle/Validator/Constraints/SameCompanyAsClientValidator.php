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

namespace SolidInvoice\TaxBundle\Validator\Constraints;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SameCompanyAsClientValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof SameCompanyAsClient) {
            throw new UnexpectedTypeException($constraint, SameCompanyAsClient::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof TaxIdentifier) {
            throw new UnexpectedValueException($value, TaxIdentifier::class);
        }

        $client = $value->getClient();

        if (! $client instanceof Client) {
            return;
        }

        $identifierCompany = $value->getCompany();
        $clientCompany = $client->getCompany();

        if ($identifierCompany->getId()->toRfc4122() !== $clientCompany->getId()->toRfc4122()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('client')
                ->addViolation();
        }
    }
}
