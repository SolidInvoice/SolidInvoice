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

namespace SolidInvoice\InvoiceBundle\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

/**
 * Class-level constraint that enforces the hosted plan's "invoices per month"
 * limit when creating a new invoice. Because validation runs before persistence
 * on both the UI form submit and the API Platform POST, this single constraint
 * closes the limit on every channel.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Validator\Constraints\WithinPlanInvoiceLimitValidatorTest
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class WithinPlanInvoiceLimit extends Constraint
{
    public string $message = 'You have reached the maximum number of invoices allowed on your current plan this month.';

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
