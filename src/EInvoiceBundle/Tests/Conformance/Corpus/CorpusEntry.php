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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus;

use const JSON_THROW_ON_ERROR;
use JsonException;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;

/**
 * One corpus in corpus.lock.json, and the state of its payload on disk.
 */
final readonly class CorpusEntry
{
    /**
     * Written next to the payload by scripts/fetch-einvoicing-corpus.php.
     */
    public const string STAMP_FILE = '.fetched.json';

    /**
     * @param string $pin the commit a clone must resolve to, or the sha256 a zip must hash to
     * @param list<string> $paths
     */
    public function __construct(
        public string $id,
        public string $title,
        public CorpusKind $kind,
        public string $url,
        public string $ref,
        public string $pin,
        public string $licence,
        public string $licenceUrl,
        public bool $committed,
        public array $paths,
        public int $minCases,
        private string $directory,
    ) {
    }

    /**
     * The manifest key the pin is recorded under, in corpus.lock.json and in the fetch stamp.
     */
    public function pinKey(): string
    {
        return match ($this->kind) {
            CorpusKind::Git => 'commit',
            CorpusKind::Zip => 'sha256',
        };
    }

    /**
     * Absolute path to the payload directory. The directory need not exist.
     */
    public function directory(): string
    {
        return $this->directory;
    }

    public function stampPath(): string
    {
        return $this->directory . '/' . self::STAMP_FILE;
    }

    /**
     * The pin recorded by the last fetch, or null when the corpus was never fetched or the stamp is unreadable.
     */
    public function fetchedPin(): ?string
    {
        if (! is_file($this->stampPath())) {
            return null;
        }

        $contents = file_get_contents($this->stampPath());

        if (false === $contents) {
            return null;
        }

        try {
            $stamp = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($stamp)) {
            return null;
        }

        $pin = $stamp[$this->pinKey()] ?? null;

        return is_string($pin) ? $pin : null;
    }

    public function isFetched(): bool
    {
        return $this->pin === $this->fetchedPin();
    }

    /**
     * @throws CorpusNotFetchedException when the payload is missing or pinned to another ref
     */
    public function assertFetched(): void
    {
        if ($this->isFetched()) {
            return;
        }

        $fetchedPin = $this->fetchedPin();

        throw null === $fetchedPin
            ? CorpusNotFetchedException::missing($this)
            : CorpusNotFetchedException::stale($this, $fetchedPin);
    }
}
