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

use const PATHINFO_FILENAME;
use DOMDocument;
use DOMElement;
use DOMNode;
use RuntimeException;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Profile;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\RuleCase;
use function basename;
use function dirname;
use function file_get_contents;
use function implode;
use function in_array;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function pathinfo;
use function sprintf;
use function trim;

/**
 * Parses a Difi/VEFA <testSet> document into the RuleCase fragments it holds.
 *
 * Each <test> child yields at most one RuleCase: its single non-<assert> element child is the
 * document fragment, imported into a fresh document so it serialises with its inherited
 * namespace declarations intact. A fragment rooted at <Order> is not a billing document and is
 * never yielded (Peppol trap 3): the test index still advances so ids stay stable.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Conformance\TestSetParserTest
 */
final class TestSetParser
{
    /**
     * Also used by CorpusIntegrityTest, which counts raw <test> elements independently of the
     * Order-fragment filtering below: minCases in corpus.lock.json is a corpus-integrity floor
     * on the raw content the corpus shipped, not on what actually reaches the engine.
     */
    public const string NAMESPACE = 'http://difi.no/xsd/vefa/validator/1.0';

    /**
     * @return iterable<RuleCase>
     */
    public static function parse(string $filePath, string $corpusId): iterable
    {
        $testSet = self::load($filePath)->documentElement;

        if (! $testSet instanceof DOMElement || self::NAMESPACE !== $testSet->namespaceURI || 'testSet' !== $testSet->localName) {
            throw new RuntimeException(sprintf('"%s" is not a VEFA <testSet> document.', $filePath));
        }

        try {
            $profile = Profile::fromConfiguration($testSet->getAttribute('configuration'));
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('"%s": %s', $filePath, $e->getMessage()), $e->getCode(), previous: $e);
        }

        self::requireScopePresent($testSet, $filePath);

        // A directory can hold two testSets for the same rule (BR-CO-15.xml and BR-CO-15-2.xml
        // both carry <scope>BR-CO-15</scope>), and one testSet can carry more than one <scope>
        // (UBL-IN_DE-R-023.xml has two). Neither is unique enough for a case id: the file
        // basename is, because the filesystem itself enforces that.
        $directoryLabel = basename(dirname($filePath));
        $label = pathinfo($filePath, PATHINFO_FILENAME);
        $testIndex = 0;

        foreach (self::children($testSet, 'test') as $test) {
            ++$testIndex;

            $documentElement = self::documentElement($test, $filePath, $testIndex);

            if (! $documentElement instanceof DOMElement) {
                continue;
            }

            $assert = self::onlyChild($test, 'assert', $filePath);
            $assertions = self::readAssertions($assert, $filePath, $testIndex);

            yield new RuleCase(
                id: self::caseId($corpusId, $directoryLabel, $label, $testIndex, $assertions['ordered']),
                document: self::serialise($documentElement),
                profile: $profile,
                expectSuccess: $assertions['success'],
                expectError: $assertions['error'],
                expectWarning: $assertions['warning'],
                description: $assertions['description'],
            );
        }
    }

    private static function load(string $filePath): DOMDocument
    {
        $contents = file_get_contents($filePath);

        if (false === $contents) {
            throw new RuntimeException(sprintf('"%s" cannot be read.', $filePath));
        }

        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument();
            $loaded = $document->loadXML($contents, LIBXML_NONET);
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        if (! $loaded) {
            throw new RuntimeException(sprintf('"%s" is not well-formed XML: %s', $filePath, $errors[0]->message ?? 'unknown error'));
        }

        return $document;
    }

    /**
     * A testSet may declare more than one <scope> (UBL-IN_DE-R-023.xml does); this only checks
     * that at least one, non-empty, is there. The case id is built from the file basename, not
     * from the scope text, so no scope value needs to be read back out.
     */
    private static function requireScopePresent(DOMElement $testSet, string $filePath): void
    {
        $assert = self::onlyChild($testSet, 'assert', $filePath);

        foreach (self::children($assert, 'scope') as $scope) {
            if ('' !== trim($scope->textContent)) {
                return;
            }
        }

        throw new RuntimeException(sprintf('"%s" has no non-empty top-level <scope>.', $filePath));
    }

    /**
     * The <test> element's single child that is not <assert>: the document fragment. Returns
     * null when that fragment is rooted at <Order>, which trap 3 says must never reach the
     * engine.
     */
    private static function documentElement(DOMElement $test, string $filePath, int $testIndex): ?DOMElement
    {
        $candidates = [];

        foreach ($test->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (self::NAMESPACE === $child->namespaceURI && 'assert' === $child->localName) {
                continue;
            }

            $candidates[] = $child;
        }

        if (1 !== count($candidates)) {
            throw new RuntimeException(sprintf('"%s" test #%d does not hold exactly one document element.', $filePath, $testIndex));
        }

        return 'Order' === $candidates[0]->localName ? null : $candidates[0];
    }

    /**
     * @return array{success: list<string>, error: list<string>, warning: list<string>, description: ?string, ordered: list<array{verb: string, rule: string}>}
     */
    private static function readAssertions(DOMElement $assert, string $filePath, int $testIndex): array
    {
        $success = [];
        $error = [];
        $warning = [];
        $description = null;
        $ordered = [];

        foreach ($assert->childNodes as $child) {
            if (! $child instanceof DOMElement || self::NAMESPACE !== $child->namespaceURI) {
                continue;
            }

            $value = trim($child->textContent);

            if ('description' === $child->localName) {
                $description = $value;

                continue;
            }

            if (! in_array($child->localName, ['success', 'error', 'warning'], true)) {
                throw new RuntimeException(sprintf('"%s" test #%d has an unexpected <%s> under <assert>.', $filePath, $testIndex, $child->localName));
            }

            $ordered[] = ['verb' => $child->localName, 'rule' => $value];

            match ($child->localName) {
                'success' => $success[] = $value,
                'error' => $error[] = $value,
                'warning' => $warning[] = $value,
            };
        }

        if ([] === $ordered) {
            throw new RuntimeException(sprintf('"%s" test #%d has no <success>, <error>, or <warning> assertions.', $filePath, $testIndex));
        }

        return ['success' => $success, 'error' => $error, 'warning' => $warning, 'description' => $description, 'ordered' => $ordered];
    }

    /**
     * @param list<array{verb: string, rule: string}> $ordered
     */
    private static function caseId(string $corpusId, string $directoryLabel, string $label, int $testIndex, array $ordered): string
    {
        $clause = implode(', ', array_map(
            static fn (array $assertion): string => sprintf('%s %s', $assertion['verb'], $assertion['rule']),
            $ordered,
        ));

        return sprintf('%s/%s/%s#%d (%s)', $corpusId, $directoryLabel, $label, $testIndex, $clause);
    }

    private static function serialise(DOMElement $element): string
    {
        $fragment = new DOMDocument('1.0', 'UTF-8');
        $imported = $fragment->importNode($element, true);
        $fragment->appendChild($imported);

        $xml = $fragment->saveXML();

        return false !== $xml ? $xml : throw new RuntimeException('Failed to serialise a conformance case document fragment.');
    }

    /**
     * @return iterable<DOMElement>
     */
    private static function children(DOMElement $parent, string $localName): iterable
    {
        /** @var DOMNode $child */
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && self::NAMESPACE === $child->namespaceURI && $localName === $child->localName) {
                yield $child;
            }
        }
    }

    private static function onlyChild(DOMElement $parent, string $localName, string $filePath): DOMElement
    {
        $found = null;

        foreach (self::children($parent, $localName) as $child) {
            if (null !== $found) {
                throw new RuntimeException(sprintf('"%s" has more than one <%s> under <%s>.', $filePath, $localName, $parent->localName));
            }

            $found = $child;
        }

        return $found ?? throw new RuntimeException(sprintf('"%s" is missing <%s> under <%s>.', $filePath, $localName, $parent->localName));
    }
}
