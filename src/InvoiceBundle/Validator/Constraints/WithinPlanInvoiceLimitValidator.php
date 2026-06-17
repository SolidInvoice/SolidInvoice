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

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Validator\Constraints\WithinPlanInvoiceLimitValidatorTest
 */
final class WithinPlanInvoiceLimitValidator extends ConstraintValidator
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly FeatureGate $featureGate,
        private readonly ClockInterface $clock,
        private readonly ToggleInterface $toggle,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof WithinPlanInvoiceLimit) {
            throw new UnexpectedTypeException($constraint, WithinPlanInvoiceLimit::class);
        }

        if (! $value instanceof Invoice) {
            throw new UnexpectedValueException($value, Invoice::class);
        }

        // SaaS-only. On self-hosted the Noop feature gate allows everything anyway,
        // but the toggle guard avoids the count query and makes the intent explicit.
        if (! $this->toggle->isActive('saas_enabled')) {
            return;
        }

        // Only newly-created invoices count against the monthly limit; a managed
        // (already-persisted) entity is an edit and must not be blocked.
        if ($this->entityManager->contains($value)) {
            return;
        }

        if (! $this->featureGate->canUse(Feature::InvoicesPerMonth->value, $this->invoiceRepository->countCreatedInMonth($this->clock->now()))) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
