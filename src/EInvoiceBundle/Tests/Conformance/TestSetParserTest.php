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

use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Profile;
use function dirname;
use function iterator_to_array;

/**
 * Unit coverage of TestSetParser against a small, hand-written sample of testSet documents.
 * These fixtures are ours, so committing them raises no licence question, unlike the real
 * corpora TestSetParser is otherwise run against.
 */
#[Group('conformance')]
#[CoversClass(TestSetParser::class)]
final class TestSetParserTest extends TestCase
{
    private const string FIXTURE_CORPUS_ID = 'fixture';

    public function testMultiRuleAssertsYieldAllRuleIdsAndOrderRootedFragmentsAreFiltered(): void
    {
        $cases = iterator_to_array(TestSetParser::parse($this->fixture('multi-rule.xml'), self::FIXTURE_CORPUS_ID), false);

        self::assertCount(2, $cases, 'The Order-rooted second <test> must never be yielded.');

        $multiRule = $cases[0];
        self::assertSame('fixture/parser/multi-rule#1 (error BR-01, error BR-02)', $multiRule->id());
        self::assertSame(['BR-01', 'BR-02'], $multiRule->expectError);
        self::assertSame([], $multiRule->expectSuccess);
        self::assertSame([], $multiRule->expectWarning);
        self::assertSame(Profile::Tc434Ubl, $multiRule->profile);

        $thirdTest = $cases[1];
        self::assertSame('fixture/parser/multi-rule#3 (success BR-01)', $thirdTest->id());
        self::assertSame(['BR-01'], $thirdTest->expectSuccess);
    }

    public function testWarningAssertionsArePreservedAndTheFragmentKeepsItsInheritedNamespace(): void
    {
        $cases = iterator_to_array(TestSetParser::parse($this->fixture('warning-and-namespace.xml'), self::FIXTURE_CORPUS_ID), false);

        self::assertCount(1, $cases);

        $case = $cases[0];
        self::assertSame(['NS-01'], $case->expectWarning);
        self::assertSame([], $case->expectError);
        self::assertSame([], $case->expectSuccess);
        self::assertSame('Fires a warning; the fragment must keep the inherited cbc: namespace', $case->description);

        $reparsed = new DOMDocument();
        $reparsed->loadXML($case->document);

        self::assertSame(
            1,
            $reparsed->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'ID')->length,
            'The cbc:ID element, whose namespace was only declared on the ancestor <testSet>, must survive extraction.',
        );
    }

    public function testATestSetWithMoreThanOneTopLevelScopeIsNotAParserFailure(): void
    {
        $cases = iterator_to_array(TestSetParser::parse($this->fixture('multi-scope.xml'), self::FIXTURE_CORPUS_ID), false);

        self::assertCount(1, $cases);
        self::assertSame('fixture/parser/multi-scope#1 (error DE-R-023-1, error DE-R-023-2)', $cases[0]->id());
    }

    public function testAMissingDescriptionIsNull(): void
    {
        $cases = iterator_to_array(TestSetParser::parse($this->fixture('missing-description.xml'), self::FIXTURE_CORPUS_ID), false);

        self::assertCount(1, $cases);
        self::assertNull($cases[0]->description);
        self::assertSame(Profile::PeppolBisBase30Ubl, $cases[0]->profile);
    }

    public function testAnUnmappedConfigurationIsAParserFailure(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not-a-real-profile/');

        iterator_to_array(TestSetParser::parse($this->fixture('unmapped-configuration.xml'), self::FIXTURE_CORPUS_ID), false);
    }

    private function fixture(string $name): string
    {
        return dirname(__DIR__) . '/Fixtures/parser/' . $name;
    }
}
