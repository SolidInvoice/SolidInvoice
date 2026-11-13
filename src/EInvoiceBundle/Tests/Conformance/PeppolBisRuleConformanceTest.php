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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\ConformanceCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\RuleCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine\ConformanceEngine;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine\ConformanceEngineFactory;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine\ConformanceReport;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine\Severity;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider\PeppolBisRuleCaseProvider;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider\UnavailableCorpusCase;
use function sprintf;

/**
 * Every Peppol BIS Billing 3.0 rule fragment (core and national), validated under its VEFA
 * testSet profile with XSD checking off. Only the rules the fragment's <assert> names are
 * checked; every other violation the engine reports is ignored.
 *
 * No validation engine exists yet, so every case here reports incomplete, not passed
 * (SOL-108 / GH #2673 builds the engine that lets this test assert for real).
 */
#[Group('conformance')]
final class PeppolBisRuleConformanceTest extends TestCase
{
    #[DataProvider('cases')]
    public function testFragmentConformsToItsAssertedRules(ConformanceCase $case): void
    {
        $engine = ConformanceEngineFactory::create();

        if (! $engine instanceof ConformanceEngine) {
            self::markTestIncomplete('No conformance engine is wired yet (SOL-108 / GH #2673).');
        }

        if ($case instanceof UnavailableCorpusCase) {
            self::markTestSkipped($case->message);
        }

        self::assertInstanceOf(RuleCase::class, $case);

        $report = $engine->validate($case->document, $case->profile, false);

        foreach ($case->expectError as $ruleId) {
            self::assertTrue($this->firesAt($report, $ruleId, Severity::Fatal), sprintf('Expected rule "%s" to fire at error severity.', $ruleId));
        }

        foreach ($case->expectWarning as $ruleId) {
            self::assertTrue($this->firesAt($report, $ruleId, Severity::Warning), sprintf('Expected rule "%s" to fire at warning severity.', $ruleId));
        }

        foreach ($case->expectSuccess as $ruleId) {
            self::assertFalse($this->fires($report, $ruleId), sprintf('Expected rule "%s" not to fire.', $ruleId));
        }
    }

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function cases(): iterable
    {
        return PeppolBisRuleCaseProvider::cases();
    }

    private function firesAt(ConformanceReport $report, string $ruleId, Severity $severity): bool
    {
        return array_any($report->violations, fn ($violation) => $ruleId === $violation->ruleId && $severity === $violation->severity);
    }

    private function fires(ConformanceReport $report, string $ruleId): bool
    {
        return array_any($report->violations, fn ($violation) => $ruleId === $violation->ruleId);
    }
}
