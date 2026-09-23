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
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusEntry;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use function file_get_contents;
use function sprintf;

/**
 * Guards the corpus pin itself, so a fetched-but-moved corpus fails loudly instead of leaving the
 * conformance suite green and measuring nothing. An unfetched corpus skips instead, since neither
 * a local checkout nor CI (until SOL-141) is guaranteed to have run the fetch.
 *
 * This test needs no validation engine.
 */
#[Group('conformance')]
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
     * Skips when nothing has been fetched. When something has been fetched, it must match the pin
     * in corpus.lock.json: a stale fetch is a real defect, not a reason to skip.
     */
    #[DataProvider('corpusProvider')]
    public function testEveryFetchedCorpusMatchesItsPin(CorpusEntry $entry): void
    {
        if (null === $entry->fetchedPin()) {
            self::markTestSkipped(sprintf(
                'The "%s" conformance corpus is not fetched. Run "composer conformance:fetch".',
                $entry->id,
            ));
        }

        $entry->assertFetched();

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
}
