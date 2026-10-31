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
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\InstanceCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine\ConformanceEngine;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine\ConformanceEngineFactory;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider\InstanceCaseProvider;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider\UnavailableCorpusCase;

/**
 * Every whole, schema-valid billing document in the three conformance corpora, validated with
 * both XSD checking and the rule engine on. Unlike the fragment tests, this asserts a single
 * document-level Verdict per case.
 *
 * No validation engine exists yet, so every case here reports incomplete, not passed
 * (SOL-108 / GH #2673 builds the engine that lets this test assert for real).
 */
#[Group('conformance')]
final class InstanceConformanceTest extends TestCase
{
    #[DataProvider('cases')]
    public function testDocumentMatchesItsExpectedVerdict(ConformanceCase $case): void
    {
        $engine = ConformanceEngineFactory::create();

        if (! $engine instanceof ConformanceEngine) {
            self::markTestIncomplete('No conformance engine is wired yet (SOL-108 / GH #2673).');
        }

        if ($case instanceof UnavailableCorpusCase) {
            self::markTestSkipped($case->message);
        }

        self::assertInstanceOf(InstanceCase::class, $case);

        $report = $engine->validate($case->document, $case->profile, true);

        self::assertSame($case->expectedVerdict, $report->verdict);
    }

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function cases(): iterable
    {
        return InstanceCaseProvider::cases();
    }
}
