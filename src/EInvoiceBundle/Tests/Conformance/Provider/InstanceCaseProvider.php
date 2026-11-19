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

use RuntimeException;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\ConformanceCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\InstanceCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Profile;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Syntax;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use function file_get_contents;
use function ltrim;
use function sprintf;
use function strlen;
use function substr;

/**
 * Whole, schema-valid billing documents: KoSIT instances, and the example/testfiles documents
 * shipped alongside the EN 16931 and Peppol rule corpora. Unlike the rule providers, every one
 * of these gets XSD validation as well as the rule engine, and produces a document-level
 * Verdict instead of per-rule expectations.
 */
final class InstanceCaseProvider
{
    /**
     * corpusId => the relative directories under that corpus holding whole documents.
     *
     * @var array<string, list<string>>
     */
    private const array SOURCES = [
        'en16931' => ['ubl/examples', 'cii/examples', 'test/testfiles'],
        'peppol-bis-3' => ['rules/examples', 'rules/national-examples'],
        'xrechnung' => ['instances'],
    ];

    /**
     * @return iterable<string, array{ConformanceCase}>
     */
    public static function cases(): iterable
    {
        $manifest = CorpusManifest::default();
        $expectations = InstanceExpectations::load();

        foreach (self::SOURCES as $corpusId => $relativeDirectories) {
            $entry = $manifest->get($corpusId);

            if (! $entry->isFetched()) {
                $sentinel = UnavailableCorpusCase::for($entry);

                yield $sentinel->id() => [$sentinel];

                continue;
            }

            foreach ($relativeDirectories as $relativeDirectory) {
                foreach (CorpusFiles::xml($entry->directory() . '/' . $relativeDirectory) as $file) {
                    $relativePath = ltrim(substr($file, strlen($entry->directory())), '/');
                    $id = $corpusId . '/' . $relativePath;

                    $document = file_get_contents($file);

                    if (false === $document) {
                        throw new RuntimeException(sprintf('"%s" cannot be read.', $file));
                    }

                    $case = new InstanceCase(
                        $id,
                        $document,
                        self::profileFor($corpusId, Syntax::detect($document)),
                        $expectations->verdictFor($id),
                    );

                    yield $case->id() => [$case];
                }
            }
        }
    }

    private static function profileFor(string $corpusId, Syntax $syntax): Profile
    {
        return match ($corpusId) {
            'xrechnung' => Profile::XRechnung,
            'en16931' => Syntax::Cii === $syntax ? Profile::Tc434Cii : Profile::Tc434Ubl,
            'peppol-bis-3' => Syntax::Cii === $syntax ? Profile::PeppolBisBase30Cii : Profile::PeppolBisBase30Ubl,
            default => throw new RuntimeException(sprintf('"%s" is not a known instance corpus.', $corpusId)),
        };
    }
}
