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
use SolidInvoice\EInvoiceBundle\Profile\RuleSet;
use SolidInvoice\EInvoiceBundle\Validation\ValidationReport;
use SolidInvoice\EInvoiceBundle\Validation\ValidationViolation;

final class ValidationReportTest extends TestCase
{
    public function testMergeWithNoReportsReturnsNotValidated(): void
    {
        $report = ValidationReport::merge();

        self::assertSame(ValidationOutcome::NotValidated, $report->outcome);
        self::assertSame([], $report->violations);
        self::assertSame([], $report->ruleSets);
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

        $report = new ValidationReport(ValidationOutcome::Invalid, [$error, $warning]);

        self::assertSame([$error], $report->errors());
        self::assertSame([$warning], $report->warnings());
    }

    public function testViolationCarriesADisjunctionOfBusinessTerms(): void
    {
        $violation = new ValidationViolation(
            ruleId: 'BR-CO-09',
            severity: ViolationSeverity::Error,
            stage: ValidationStage::Business,
            businessTerms: ['BT-31', 'BT-63', 'BT-48'],
            xpath: null,
            message: 'The Seller VAT identifier, the Seller tax registration identifier and the Seller tax representative VAT identifier must not all be blank.',
        );

        self::assertSame(['BT-31', 'BT-63', 'BT-48'], $violation->businessTerms);
    }

    public function testMergeUnionsDifferentRuleSetsInFirstSeenOrder(): void
    {
        $schematron = new RuleSet('peppol-bis-3', 'v3.0.20', ValidationStage::Business, '261c458474e27d58a25be629cccac28883171c92');
        $xsd = new RuleSet('cen-ubl', '1.3.12', ValidationStage::Schema);

        $report = ValidationReport::merge(
            new ValidationReport(ValidationOutcome::Valid, ruleSets: [$schematron]),
            new ValidationReport(ValidationOutcome::Valid, ruleSets: [$xsd]),
        );

        self::assertSame([$schematron, $xsd], $report->ruleSets);
    }

    public function testMergeDeduplicatesEqualRuleSetsByValue(): void
    {
        $first = new RuleSet('peppol-bis-3', 'v3.0.20', ValidationStage::Business, '261c458474e27d58a25be629cccac28883171c92');
        $second = new RuleSet('peppol-bis-3', 'v3.0.20', ValidationStage::Business, '261c458474e27d58a25be629cccac28883171c92');

        $report = ValidationReport::merge(
            new ValidationReport(ValidationOutcome::Valid, ruleSets: [$first]),
            new ValidationReport(ValidationOutcome::Valid, ruleSets: [$second]),
        );

        self::assertCount(1, $report->ruleSets);
    }

    public function testMergeTreatsCommitAsPartOfRuleSetIdentity(): void
    {
        $pinned = new RuleSet('peppol-bis-3', 'v3.0.20', ValidationStage::Business, '261c458474e27d58a25be629cccac28883171c92');
        $unpinned = new RuleSet('peppol-bis-3', 'v3.0.20', ValidationStage::Business);

        $report = ValidationReport::merge(
            new ValidationReport(ValidationOutcome::Valid, ruleSets: [$pinned]),
            new ValidationReport(ValidationOutcome::Valid, ruleSets: [$unpinned]),
        );

        self::assertCount(2, $report->ruleSets);
    }

    private function violation(ViolationSeverity $severity): ValidationViolation
    {
        return new ValidationViolation(
            ruleId: 'BR-06',
            severity: $severity,
            stage: ValidationStage::Business,
            businessTerms: ['BT-27'],
            xpath: null,
            message: 'Seller name is required.',
        );
    }
}
