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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusEntry;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusKind;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusNotFetchedException;
use function file_get_contents;
use function getenv;
use function sprintf;

/**
 * Guards the corpus pin itself, so an absent or moved corpus fails loudly instead of leaving the
 * conformance suite green and measuring nothing.
 *
 * This test needs no validation engine and no fetched corpus outside CI.
 */
#[Group('conformance')]
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
     * On CI the corpus must be there and must match the pin. Locally it may be absent, because
     * fetching it is a deliberate step.
     */
    #[DataProvider('corpusProvider')]
    public function testEveryCorpusIsFetchedOnCi(CorpusEntry $entry): void
    {
        if (! $entry->isFetched() && false === (bool) getenv('CI')) {
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
