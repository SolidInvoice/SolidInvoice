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

use const JSON_THROW_ON_ERROR;
use JsonException;
use RuntimeException;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Verdict;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;

/**
 * Reads src/EInvoiceBundle/Tests/Fixtures/instance-expectations.json, the committed record of
 * which whole-document conformance cases must be rejected. Anything not listed defaults to
 * accept: the verdict is never inferred from the filename, since that is exactly how a corpus
 * bump would silently flip an expectation without anyone noticing.
 */
final readonly class InstanceExpectations
{
    private const string FILE = 'instance-expectations.json';

    /**
     * @param array<string, Verdict> $overrides
     */
    private function __construct(
        private array $overrides,
    ) {
    }

    public static function load(): self
    {
        $path = CorpusManifest::fixturesDirectory() . '/' . self::FILE;
        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new RuntimeException(sprintf('"%s" cannot be read.', $path));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('"%s" is not valid JSON: %s', $path, $e->getMessage()), $e->getCode(), previous: $e);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('"%s" is not a JSON object.', $path));
        }

        $overrides = [];

        foreach ($decoded as $id => $verdict) {
            if (! is_string($id) || ! is_string($verdict)) {
                throw new RuntimeException(sprintf('"%s" holds a non-string key or value.', $path));
            }

            $overrides[$id] = Verdict::from($verdict);
        }

        return new self($overrides);
    }

    public function verdictFor(string $id): Verdict
    {
        return $this->overrides[$id] ?? Verdict::Accept;
    }
}
