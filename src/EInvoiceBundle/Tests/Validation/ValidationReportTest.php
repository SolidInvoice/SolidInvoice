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

namespace SolidInvoice\EInvoiceBundle\Tests\Validation;

use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\ValidationOutcome;
use SolidInvoice\EInvoiceBundle\Enum\ValidationStage;
use SolidInvoice\EInvoiceBundle\Enum\ViolationSeverity;
use SolidInvoice\EInvoiceBundle\Validation\ValidationReport;
use SolidInvoice\EInvoiceBundle\Validation\ValidationViolation;

final class ValidationReportTest extends TestCase
{
    public function testMergeWithNoReportsReturnsNotValidated(): void
    {
        $report = ValidationReport::merge();

        self::assertSame(ValidationOutcome::NotValidated, $report->outcome);
        self::assertSame([], $report->violations);
        self::assertFalse($report->isValid());
    }

    public function testMergePrefersInvalidOverEverything(): void
    {
        $valid = new ValidationReport(ValidationOutcome::Valid);
        $notValidated = new ValidationReport(ValidationOutcome::NotValidated);
        $invalid = new ValidationReport(ValidationOutcome::Invalid);

        $report = ValidationReport::merge($valid, $notValidated, $invalid);

        self::assertSame(ValidationOutcome::Invalid, $report->outcome);
    }

    public function testMergePrefersNotValidatedOverValid(): void
    {
        $valid = new ValidationReport(ValidationOutcome::Valid);
        $notValidated = new ValidationReport(ValidationOutcome::NotValidated);

        $report = ValidationReport::merge($valid, $notValidated);

        self::assertSame(ValidationOutcome::NotValidated, $report->outcome);
    }

    public function testMergeOfOnlyValidReportsIsValid(): void
    {
        $report = ValidationReport::merge(
            new ValidationReport(ValidationOutcome::Valid),
            new ValidationReport(ValidationOutcome::Valid),
        );

        self::assertSame(ValidationOutcome::Valid, $report->outcome);
    }

    public function testIsValidReturnsFalseForNotValidatedWithNoViolations(): void
    {
        $report = new ValidationReport(ValidationOutcome::NotValidated, []);

        self::assertFalse($report->isValid());
    }

    public function testMergeConcatenatesViolationsInArgumentOrder(): void
    {
        $first = $this->violation(ViolationSeverity::Error);
        $second = $this->violation(ViolationSeverity::Warning);

        $report = ValidationReport::merge(
            new ValidationReport(ValidationOutcome::Invalid, [$first]),
            new ValidationReport(ValidationOutcome::Valid, [$second]),
        );

        self::assertSame([$first, $second], $report->violations);
    }

    public function testErrorsAndWarningsFilterBySeverity(): void
    {
        $error = $this->violation(ViolationSeverity::Error);
        $warning = $this->violation(ViolationSeverity::Warning);
        $information = $this->violation(ViolationSeverity::Information);

        $report = new ValidationReport(ValidationOutcome::Invalid, [$error, $warning, $information]);

        self::assertSame([$error], $report->errors());
        self::assertSame([$warning], $report->warnings());
    }

    private function violation(ViolationSeverity $severity): ValidationViolation
    {
        return new ValidationViolation(
            ruleId: 'BR-06',
            severity: $severity,
            stage: ValidationStage::Business,
            businessTerm: 'BT-27',
            xpath: null,
            message: 'Seller name is required.',
        );
    }
}
