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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider;

use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\ConformanceCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusEntry;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\TestSetParser;

/**
 * The core/national split is by directory, not by configuration: national testSets declare the
 * same peppolbis-en16931-base-3.0-{ubl,cii} configuration as the core ones, so it is derived
 * here from the "unit-{UBL,CII}-<suffix>" directory name instead. A "PEPPOL" suffix is core;
 * anything else is a national extension.
 */
final class PeppolBisRuleCaseProvider
{
    private const string CORPUS_ID = 'peppol-bis-3';

    private const string UNIT_DIRECTORY_PATTERN = '/^unit-(UBL|CII)-(?<suffix>.+)$/';

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function cases(): iterable
    {
        yield from self::casesMatching(static fn (string $suffix): bool => true);
    }

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function coreCases(): iterable
    {
        yield from self::casesMatching(static fn (string $suffix): bool => 'PEPPOL' === $suffix);
    }

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function nationalCases(): iterable
    {
        yield from self::casesMatching(static fn (string $suffix): bool => 'PEPPOL' !== $suffix);
    }

    /**
     * @param callable(string): bool $suffixMatches
     * @return iterable<string, array{ConformanceCase}>
     */
    private static function casesMatching(callable $suffixMatches): iterable
    {
        $entry = CorpusManifest::default()->get(self::CORPUS_ID);

        if (! $entry->isFetched()) {
            $sentinel = UnavailableCorpusCase::for($entry);

            yield $sentinel->id() => [$sentinel];

            return;
        }

        foreach (self::unitDirectories($entry) as $directory => $suffix) {
            if (! $suffixMatches($suffix)) {
                continue;
            }

            foreach (CorpusFiles::xml($directory) as $file) {
                foreach (TestSetParser::parse($file, $entry->id) as $case) {
                    yield $case->id() => [$case];
                }
            }
        }
    }

    /**
     * @return iterable<string, string>
     */
    private static function unitDirectories(CorpusEntry $entry): iterable
    {
        return CorpusFiles::directories($entry->directory() . '/rules', self::UNIT_DIRECTORY_PATTERN);
    }
}
