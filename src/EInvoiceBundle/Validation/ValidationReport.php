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

namespace SolidInvoice\EInvoiceBundle\Validation;

use SolidInvoice\EInvoiceBundle\Enum\ValidationOutcome;
use SolidInvoice\EInvoiceBundle\Enum\ViolationSeverity;

/**
 * @see \SolidInvoice\EInvoiceBundle\Tests\Validation\ValidationReportTest
 */
final readonly class ValidationReport
{
    /**
     * @param list<ValidationViolation> $violations
     */
    public function __construct(
        public ValidationOutcome $outcome,
        public array $violations = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->outcome === ValidationOutcome::Valid;
    }

    /**
     * @return list<ValidationViolation>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->violations,
            static fn (ValidationViolation $violation): bool => $violation->severity === ViolationSeverity::Error,
        ));
    }

    /**
     * @return list<ValidationViolation>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->violations,
            static fn (ValidationViolation $violation): bool => $violation->severity === ViolationSeverity::Warning,
        ));
    }

    public static function merge(self ...$reports): self
    {
        if ($reports === []) {
            return new self(ValidationOutcome::NotValidated);
        }

        $outcome = ValidationOutcome::Valid;
        $violations = [];

        foreach ($reports as $report) {
            $violations = [...$violations, ...$report->violations];

            if ($report->outcome === ValidationOutcome::Invalid) {
                $outcome = ValidationOutcome::Invalid;
            } elseif ($report->outcome === ValidationOutcome::NotValidated && $outcome !== ValidationOutcome::Invalid) {
                $outcome = ValidationOutcome::NotValidated;
            }
        }

        return new self($outcome, $violations);
    }
}
