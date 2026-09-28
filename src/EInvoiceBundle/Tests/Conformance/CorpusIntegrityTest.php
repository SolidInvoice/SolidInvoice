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
use DOMElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusEntry;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusKind;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusNotFetchedException;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider\CorpusFiles;
use function count;
use function file_get_contents;
use function getenv;
use function sprintf;

/**
 * Guards the corpus pin itself, so an absent or moved corpus fails loudly instead of leaving the
 * conformance suite green and measuring nothing.
 *
 * This test needs no validation engine. The manifest-schema and licence checks below need no
 * corpus either, so they carry no "conformance" group tag and run on every CI leg by default.
 * Only the presence and minCases checks need a fetched corpus, so only they carry the tag: the
 * "Fetch conformance corpus" step in unit-tests.yml (SOL-141) is the only place that fetches one.
 */
#[CoversClass(CorpusEntry::class)]
#[CoversClass(CorpusKind::class)]
#[CoversClass(CorpusManifest::class)]
#[CoversClass(CorpusNotFetchedException::class)]
final class CorpusIntegrityTest extends TestCase
{
    /**
     * The licences the project has cleared. Adding one here is a licence decision, not a refactor:
     * record it in CORPUS-LICENCES.md at the same time.
     */
    private const array CLEARED_LICENCES = ['EUPL-1.2', 'Apache-2.0', 'UNLICENSED'];

    /**
     * CorpusManifest::fromFile() validates against corpus.lock.schema.json and throws otherwise.
     */
    public function testTheManifestParsesAndMatchesItsSchema(): void
    {
        $manifest = CorpusManifest::default();

        self::assertNotEmpty($manifest->entries());
        self::assertSame(['en16931', 'peppol-bis-3', 'xrechnung'], $manifest->ids());
    }

    #[DataProvider('corpusProvider')]
    public function testNoCorpusIsCommittedAndEveryOneCarriesALicence(CorpusEntry $entry): void
    {
        self::assertFalse($entry->committed, sprintf('The "%s" corpus must not be committed to this repository.', $entry->id));
        self::assertNotSame('', $entry->licence);
        self::assertNotSame('', $entry->licenceUrl);
    }

    #[DataProvider('corpusProvider')]
    public function testEveryLicenceIsClearedAndRecorded(CorpusEntry $entry): void
    {
        self::assertContains($entry->licence, self::CLEARED_LICENCES);

        $record = file_get_contents(CorpusManifest::fixturesDirectory() . '/CORPUS-LICENCES.md');

        self::assertIsString($record);
        self::assertStringContainsString(
            $entry->licence,
            $record,
            sprintf('CORPUS-LICENCES.md does not record the "%s" licence of the "%s" corpus.', $entry->licence, $entry->id),
        );
        self::assertStringContainsString(
            $entry->licenceUrl,
            $record,
            sprintf('CORPUS-LICENCES.md does not link the licence of the "%s" corpus at ref "%s".', $entry->id, $entry->ref),
        );
    }

    /**
     * Skips when the corpus was never fetched and this run's own fetch step did not run either —
     * locally, or on a CI leg that has no interest in e-invoicing. Once that fetch step exports
     * SOLIDINVOICE_CONFORMANCE_CORPUS, a still-missing corpus means the fetch step failed, so this
     * errors instead of skipping. A corpus that was fetched but does not match its pin always
     * fails, marker or not: that is a real defect, never a reason to skip.
     */
    #[Group('conformance')]
    #[DataProvider('corpusProvider')]
    public function testEveryFetchedCorpusMatchesItsPin(CorpusEntry $entry): void
    {
        if (null === $entry->fetchedPin()) {
            if ((bool) getenv('SOLIDINVOICE_CONFORMANCE_CORPUS')) {
                $entry->assertFetched();
            }

            self::markTestSkipped(sprintf(
                'The "%s" conformance corpus is not fetched. Run "composer conformance:fetch".',
                $entry->id,
            ));
        }

        self::assertSame($entry->pin, $entry->fetchedPin());
    }

    /**
     * @return iterable<string, array{CorpusEntry}>
     */
    public static function corpusProvider(): iterable
    {
        foreach (CorpusManifest::default()->entries() as $entry) {
            yield $entry->id => [$entry];
        }
    }

    /**
     * minCases in corpus.lock.json is a floor on the raw content the corpus shipped: it stops
     * trap 1 (the peppol-bis-3 ".xm" typo'd extension silently dropping a rule) from recurring
     * unnoticed, and stops a fetched-but-empty corpus from leaving the conformance suite green
     * while it measures nothing.
     *
     * This counts raw <test> elements, not the RuleCase objects the providers yield: those are
     * smaller, because trap 3 filters the 4 Order-rooted fragments (all of them inside
     * unit-UBL-PEPPOL) before anything reaches the engine. minCases is a corpus-integrity floor,
     * not an engine-input count, so it is measured the same way here.
     *
     * Only enforced once this run's own fetch step has set SOLIDINVOICE_CONFORMANCE_CORPUS: locally,
     * and on every other CI leg, the corpus is not committed, so fetching it is a deliberate,
     * opt-in step.
     */
    #[Group('conformance')]
    public function testEveryCorpusMeetsItsMinCasesFloorOnCi(): void
    {
        if (false === (bool) getenv('SOLIDINVOICE_CONFORMANCE_CORPUS')) {
            self::markTestSkipped('minCases floors are enforced once the CI fetch step (SOL-141) has run.');
        }

        $manifest = CorpusManifest::default();

        $en16931 = $manifest->get('en16931');
        $en16931Count = $this->countRawTests($en16931->directory(), ['test/Invoice-unit-UBL', 'test/CreditNote-unit-UBL', 'test/cii']);
        self::assertGreaterThanOrEqual($en16931->minCases, $en16931Count);

        $peppol = $manifest->get('peppol-bis-3');
        $peppolCore = $this->countRawPeppolTests($peppol, static fn (string $suffix): bool => 'PEPPOL' === $suffix);
        $peppolNational = $this->countRawPeppolTests($peppol, static fn (string $suffix): bool => 'PEPPOL' !== $suffix);
        self::assertGreaterThanOrEqual(354, $peppolCore, 'The peppol-bis-3 core raw test count dropped below the measured floor.');
        self::assertGreaterThanOrEqual(526, $peppolNational, 'The peppol-bis-3 national raw test count dropped below the measured floor.');
        self::assertGreaterThanOrEqual($peppol->minCases, $peppolCore + $peppolNational);

        $xrechnung = $manifest->get('xrechnung');
        self::assertGreaterThanOrEqual($xrechnung->minCases, count(CorpusFiles::xml($xrechnung->directory() . '/instances')));
    }

    /**
     * @param list<string> $relativeDirectories
     */
    private function countRawTests(string $corpusDirectory, array $relativeDirectories): int
    {
        $count = 0;

        foreach ($relativeDirectories as $relativeDirectory) {
            foreach (CorpusFiles::xml($corpusDirectory . '/' . $relativeDirectory) as $file) {
                $count += $this->countTestElements($file);
            }
        }

        return $count;
    }

    /**
     * @param callable(string): bool $suffixMatches
     */
    private function countRawPeppolTests(CorpusEntry $entry, callable $suffixMatches): int
    {
        $count = 0;

        foreach (CorpusFiles::directories($entry->directory() . '/rules', '/^unit-(UBL|CII)-(?<suffix>.+)$/') as $directory => $suffix) {
            if (! $suffixMatches($suffix)) {
                continue;
            }

            foreach (CorpusFiles::xml($directory) as $file) {
                $count += $this->countTestElements($file);
            }
        }

        return $count;
    }

    private function countTestElements(string $file): int
    {
        $contents = file_get_contents($file);
        self::assertIsString($contents, sprintf('"%s" cannot be read.', $file));

        $document = new DOMDocument();
        self::assertTrue($document->loadXML($contents, LIBXML_NONET), sprintf('"%s" is not well-formed XML.', $file));

        $testSet = $document->documentElement;
        self::assertInstanceOf(DOMElement::class, $testSet);

        $count = 0;

        foreach ($testSet->childNodes as $child) {
            if ($child instanceof DOMElement && TestSetParser::NAMESPACE === $child->namespaceURI && 'test' === $child->localName) {
                ++$count;
            }
        }

        return $count;
    }
}
