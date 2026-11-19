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
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\TestSetParser;

/**
 * The EN 16931 rule corpus is UBL-only for CreditNote and Invoice; its CII coverage is the two
 * files under test/cii. test/testfiles is deliberately excluded here: those are whole,
 * schema-valid documents, not VEFA testSet fragments, and belong to InstanceCaseProvider.
 */
final class En16931RuleCaseProvider
{
    private const string CORPUS_ID = 'en16931';

    /**
     * @var list<string>
     */
    private const array TEST_SET_DIRECTORIES = ['test/Invoice-unit-UBL', 'test/CreditNote-unit-UBL', 'test/cii'];

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function cases(): iterable
    {
        $entry = CorpusManifest::default()->get(self::CORPUS_ID);

        if (! $entry->isFetched()) {
            $sentinel = UnavailableCorpusCase::for($entry);

            yield $sentinel->id() => [$sentinel];

            return;
        }

        foreach (self::TEST_SET_DIRECTORIES as $relativeDirectory) {
            foreach (CorpusFiles::xml($entry->directory() . '/' . $relativeDirectory) as $file) {
                foreach (TestSetParser::parse($file, $entry->id) as $case) {
                    yield $case->id() => [$case];
                }
            }
        }
    }
}
