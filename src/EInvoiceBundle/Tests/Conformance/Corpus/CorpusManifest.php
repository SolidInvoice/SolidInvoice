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
use RuntimeException;
use ValueError;
use function array_diff;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function dirname;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;

/**
 * Reads corpus.lock.json, the version pin for every external conformance corpus.
 *
 * The manifest is validated against the rules in corpus.lock.schema.json on load, so both the fetch
 * script and the test suite reject a malformed pin at the same point and with the same message.
 */
final readonly class CorpusManifest
{
    private const string MANIFEST_FILE = 'corpus.lock.json';

    private const string SCHEMA_FILE = 'corpus.lock.schema.json';

    private const string PAYLOAD_DIRECTORY = 'corpus';

    /**
     * @var list<string>
     */
    private const array REQUIRED_KEYS = ['id', 'title', 'kind', 'url', 'ref', 'licence', 'licenceUrl', 'committed', 'paths', 'minCases'];

    /**
     * @var list<string>
     */
    private const array KNOWN_KEYS = [...self::REQUIRED_KEYS, 'commit', 'sha256'];

    /**
     * Mirrors the "licence" enum in corpus.lock.schema.json.
     *
     * @var list<string>
     */
    private const array CLEARED_LICENCES = ['EUPL-1.2', 'Apache-2.0', 'UNLICENSED'];

    private const string HTTPS_PATTERN = '/^https:\/\//';

    private const string ID_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    private const string COMMIT_PATTERN = '/^[0-9a-f]{40}$/';

    private const string SHA256_PATTERN = '/^[0-9a-f]{64}$/';

    /**
     * @param array<string, CorpusEntry> $entries
     */
    private function __construct(
        private string $path,
        private array $entries,
    ) {
    }

    public static function default(): self
    {
        return self::fromFile(self::fixturesDirectory() . '/' . self::MANIFEST_FILE);
    }

    public static function fromFile(string $path): self
    {
        $manifest = self::read($path);
        self::assertSchemaFileIsValidJson(dirname($path) . '/' . self::SCHEMA_FILE);

        $corpora = $manifest['corpora'] ?? null;

        if (! is_array($corpora) || ! array_is_list($corpora) || [] === $corpora) {
            throw new RuntimeException(sprintf('"%s" does not hold a non-empty list of corpora.', $path));
        }

        $payloadDirectory = dirname($path) . '/' . self::PAYLOAD_DIRECTORY;
        $entries = [];

        foreach ($corpora as $index => $corpus) {
            if (! is_array($corpus)) {
                throw new RuntimeException(sprintf('Corpus %d in "%s" is not an object.', $index, $path));
            }

            $entry = self::parseEntry($corpus, $path, $payloadDirectory);
            $entries[$entry->id] = $entry;
        }

        return new self($path, $entries);
    }

    /**
     * The directory holding corpus.lock.json, its schema, and the licence record.
     */
    public static function fixturesDirectory(): string
    {
        return dirname(__DIR__, 2) . '/Fixtures';
    }

    public static function schemaPath(): string
    {
        return self::fixturesDirectory() . '/' . self::SCHEMA_FILE;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return list<CorpusEntry>
     */
    public function entries(): array
    {
        return array_values($this->entries);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->entries);
    }

    public function get(string $id): CorpusEntry
    {
        return $this->entries[$id] ?? throw new RuntimeException(sprintf(
            'There is no corpus "%s" in "%s". Known corpora: %s.',
            $id,
            $this->path,
            implode(', ', $this->ids()),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private static function read(string $path): array
    {
        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new RuntimeException(sprintf('The corpus manifest "%s" cannot be read.', $path));
        }

        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('The corpus manifest "%s" is not valid JSON: %s', $path, $e->getMessage()), $e->getCode(), previous: $e);
        }

        if (! is_array($manifest) || array_is_list($manifest)) {
            throw new RuntimeException(sprintf('The corpus manifest "%s" is not a JSON object.', $path));
        }

        return $manifest;
    }

    /**
     * The schema file is documentation and editor tooling support, not a generic validator input.
     * The rules it describes are checked directly against the manifest below, so this only confirms
     * the committed schema file itself is not broken.
     */
    private static function assertSchemaFileIsValidJson(string $schemaPath): void
    {
        $schema = file_get_contents($schemaPath);

        if (false === $schema) {
            throw new RuntimeException(sprintf('The corpus manifest schema "%s" cannot be read.', $schemaPath));
        }

        try {
            json_decode($schema, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('The corpus manifest schema "%s" is not valid JSON: %s', $schemaPath, $e->getMessage()), $e->getCode(), previous: $e);
        }
    }

    /**
     * @param array<array-key, mixed> $corpus
     */
    private static function parseEntry(array $corpus, string $path, string $payloadDirectory): CorpusEntry
    {
        $unknown = array_diff(array_keys($corpus), self::KNOWN_KEYS);

        if ([] !== $unknown) {
            throw new RuntimeException(sprintf('Corpus in "%s" carries unknown key(s): %s.', $path, implode(', ', $unknown)));
        }

        foreach (self::REQUIRED_KEYS as $key) {
            if (! array_key_exists($key, $corpus)) {
                throw new RuntimeException(sprintf('Corpus in "%s" is missing required key "%s".', $path, $key));
            }
        }

        $id = self::patternValue($corpus, 'id', self::ID_PATTERN, $path);
        $kind = self::kindValue($corpus, $path);
        $title = self::stringValue($corpus, 'title', $path);

        if ('' === $title) {
            throw self::badValue('title', 'a non-empty string', $path);
        }

        $url = self::patternValue($corpus, 'url', self::HTTPS_PATTERN, $path);
        $ref = self::stringValue($corpus, 'ref', $path);

        if ('' === $ref) {
            throw self::badValue('ref', 'a non-empty string', $path);
        }

        $licence = self::enumValue($corpus, 'licence', self::CLEARED_LICENCES, $path);
        $licenceUrl = self::patternValue($corpus, 'licenceUrl', self::HTTPS_PATTERN, $path);
        $committed = self::boolValue($corpus, 'committed', $path);

        if ($committed) {
            throw new RuntimeException(sprintf('Corpus "%s" in "%s" has "committed": true. No corpus is committed to this repository.', $id, $path));
        }

        $paths = self::stringListValue($corpus, 'paths', $path);

        if ([] === $paths) {
            throw new RuntimeException(sprintf('Corpus "%s" in "%s" has an empty "paths" list.', $id, $path));
        }

        $minCases = self::intValue($corpus, 'minCases', $path);

        if ($minCases < 1) {
            throw new RuntimeException(sprintf('Corpus "%s" in "%s" has "minCases" below 1.', $id, $path));
        }

        $pin = match ($kind) {
            CorpusKind::Git => self::pinValue($corpus, 'commit', 'sha256', self::COMMIT_PATTERN, $path),
            CorpusKind::Zip => self::pinValue($corpus, 'sha256', 'commit', self::SHA256_PATTERN, $path),
        };

        return new CorpusEntry(
            id: $id,
            title: $title,
            kind: $kind,
            url: $url,
            ref: $ref,
            pin: $pin,
            licence: $licence,
            licenceUrl: $licenceUrl,
            committed: $committed,
            paths: $paths,
            minCases: $minCases,
            directory: $payloadDirectory . '/' . $id,
        );
    }

    /**
     * @param array<array-key, mixed> $corpus
     */
    private static function kindValue(array $corpus, string $path): CorpusKind
    {
        $value = self::stringValue($corpus, 'kind', $path);

        try {
            return CorpusKind::from($value);
        } catch (ValueError) {
            throw new RuntimeException(sprintf('Corpus key "kind" in "%s" is "%s", which is not "git" or "zip".', $path, $value));
        }
    }

    /**
     * The pin required by $kind (commit for git, sha256 for zip). The other kind's pin key must be
     * absent, and the present one must match $pattern.
     *
     * @param array<array-key, mixed> $corpus
     */
    private static function pinValue(array $corpus, string $key, string $forbiddenKey, string $pattern, string $path): string
    {
        if (array_key_exists($forbiddenKey, $corpus)) {
            throw new RuntimeException(sprintf('Corpus in "%s" carries "%s", which does not apply to this "kind".', $path, $forbiddenKey));
        }

        if (! array_key_exists($key, $corpus)) {
            throw new RuntimeException(sprintf('Corpus in "%s" is missing "%s", required for this "kind".', $path, $key));
        }

        return self::patternValue($corpus, $key, $pattern, $path);
    }

    /**
     * @param array<array-key, mixed> $corpus
     */
    private static function patternValue(array $corpus, string $key, string $pattern, string $path): string
    {
        $value = self::stringValue($corpus, $key, $path);

        if (1 !== preg_match($pattern, $value)) {
            throw new RuntimeException(sprintf('Corpus key "%s" in "%s" is "%s", which does not match the expected pattern.', $key, $path, $value));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $corpus
     * @param list<string> $allowed
     */
    private static function enumValue(array $corpus, string $key, array $allowed, string $path): string
    {
        $value = self::stringValue($corpus, $key, $path);

        if (! in_array($value, $allowed, true)) {
            throw new RuntimeException(sprintf('Corpus key "%s" in "%s" is "%s", which is not one of: %s.', $key, $path, $value, implode(', ', $allowed)));
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $corpus
     */
    private static function stringValue(array $corpus, string $key, string $path): string
    {
        $value = $corpus[$key] ?? null;

        return is_string($value) ? $value : throw self::badValue($key, 'a string', $path);
    }

    /**
     * @param array<array-key, mixed> $corpus
     */
    private static function intValue(array $corpus, string $key, string $path): int
    {
        $value = $corpus[$key] ?? null;

        return is_int($value) ? $value : throw self::badValue($key, 'an integer', $path);
    }

    /**
     * @param array<array-key, mixed> $corpus
     */
    private static function boolValue(array $corpus, string $key, string $path): bool
    {
        $value = $corpus[$key] ?? null;

        return is_bool($value) ? $value : throw self::badValue($key, 'a boolean', $path);
    }

    /**
     * @param array<array-key, mixed> $corpus
     * @return list<string>
     */
    private static function stringListValue(array $corpus, string $key, string $path): array
    {
        $value = $corpus[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value)) {
            throw self::badValue($key, 'a list of strings', $path);
        }

        return array_map(
            static fn (mixed $item): string => is_string($item) ? $item : throw self::badValue($key, 'a list of strings', $path),
            $value,
        );
    }

    private static function badValue(string $key, string $expected, string $path): RuntimeException
    {
        return new RuntimeException(sprintf('Corpus key "%s" in "%s" must be %s.', $key, $path, $expected));
    }
}
