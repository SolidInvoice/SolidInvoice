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

/*
 * Downloads the e-invoicing conformance corpora pinned in
 * src/EInvoiceBundle/Tests/Fixtures/corpus.lock.json.
 *
 * No corpus is committed to this repository. See CORPUS-LICENCES.md in that directory for the
 * licence of each corpus, and README.md for the update procedure.
 *
 *   composer conformance:fetch                 fetch everything that is missing or stale
 *   composer conformance:fetch -- --force      fetch everything again
 *   composer conformance:fetch -- --check      exit non-zero when anything is missing or stale
 */

use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusEntry;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusKind;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusManifest;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

require dirname(__DIR__) . '/vendor/autoload.php';

const USAGE = <<<'TXT'
    Usage: php scripts/fetch-einvoicing-corpus.php [--force] [--check]

      --force   Fetch every corpus again, even when the stamp matches the pin.
      --check   Exit 1 when any corpus is missing or stale. Print nothing when all are current.
      --help    Show this message.
    TXT;

/**
 * Downloads one corpus into a staging directory, verifies its pin, then keeps only the paths the
 * manifest lists.
 */
final class CorpusFetcher
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly string $temporaryDirectory,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function fetch(CorpusEntry $entry): void
    {
        $staging = $this->temporaryDirectory . '/' . $entry->id . '-' . bin2hex(random_bytes(6));
        $target = $entry->directory() . '.incoming';

        try {
            match ($entry->kind) {
                CorpusKind::Git => $this->cloneRepository($entry, $staging),
                CorpusKind::Zip => $this->download($entry, $staging),
            };

            $this->filesystem->remove($target);

            foreach ($entry->paths as $path) {
                $source = $staging . '/' . $path;

                if (! is_dir($source)) {
                    throw new RuntimeException(sprintf(
                        'The "%s" corpus at %s "%s" has no "%s" directory. Check the "paths" entry in corpus.lock.json.',
                        $entry->id,
                        $entry->kind->value,
                        $entry->ref,
                        $path,
                    ));
                }

                $this->filesystem->mirror($source, $target . '/' . $path);
            }

            $this->filesystem->dumpFile($target . '/' . CorpusEntry::STAMP_FILE, json_encode([
                'id' => $entry->id,
                $entry->pinKey() => $entry->pin,
                'fetchedAt' => Clock::get()->now()->format(DATE_ATOM),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");

            $this->filesystem->remove($entry->directory());
            $this->filesystem->rename($target, $entry->directory());
        } finally {
            $this->filesystem->remove([$staging, $staging . '.zip', $target]);
        }
    }

    /**
     * A shallow fetch of the tag, then a hard check that the tag still points at the pinned commit.
     * A moved tag is a failure, not a warning.
     *
     * The tag ref is spelled out in full. "git clone --branch <name>" resolves a branch before a
     * tag, and eInvoicing-EN16931 carries a branch and a tag that share the name
     * "validation-1.3.16" and point at different commits.
     */
    private function cloneRepository(CorpusEntry $entry, string $staging): void
    {
        $tag = 'refs/tags/' . $entry->ref;

        $this->run(['git', 'init', '--quiet', $staging]);
        $this->run(['git', '-C', $staging, 'remote', 'add', 'origin', $entry->url]);
        $this->run(['git', '-C', $staging, 'fetch', '--depth', '1', '--quiet', 'origin', $tag . ':' . $tag]);

        $head = trim($this->run(['git', '-C', $staging, 'rev-parse', $tag]));

        if ($head !== $entry->pin) {
            throw new RuntimeException(sprintf(
                'The "%s" corpus tag "%s" now points at %s, but corpus.lock.json pins %s. '
                . 'The upstream tag moved. Confirm the new commit before you change the manifest.',
                $entry->id,
                $entry->ref,
                $head,
                $entry->pin,
            ));
        }

        $this->run(['git', '-C', $staging, '-c', 'advice.detachedHead=false', 'checkout', '--quiet', $tag]);

        // We keep the corpus data, not its history.
        $this->filesystem->remove($staging . '/.git');
    }

    /**
     * Downloads the release asset and checks its sha256 before a single byte is extracted.
     */
    private function download(CorpusEntry $entry, string $staging): void
    {
        $archive = $staging . '.zip';
        $this->filesystem->mkdir(dirname($archive));

        $client = HttpClient::create();
        $response = $client->request('GET', $entry->url);
        $handle = fopen($archive, 'w');

        if (false === $handle) {
            throw new RuntimeException(sprintf('Cannot write the downloaded archive to "%s".', $archive));
        }

        try {
            foreach ($client->stream($response) as $chunk) {
                fwrite($handle, $chunk->getContent());
            }
        } finally {
            fclose($handle);
        }

        $hash = hash_file('sha256', $archive);

        if ($hash !== $entry->pin) {
            $this->filesystem->remove($archive);

            throw new RuntimeException(sprintf(
                'The "%s" archive hashes to %s, but corpus.lock.json pins %s. Nothing was extracted.',
                $entry->id,
                false === $hash ? 'nothing readable' : $hash,
                $entry->pin,
            ));
        }

        if (! extension_loaded('zip')) {
            throw new RuntimeException('The "zip" PHP extension is required to extract a zip corpus.');
        }

        $zip = new ZipArchive();

        if (true !== $zip->open($archive)) {
            throw new RuntimeException(sprintf('The "%s" archive cannot be opened.', $entry->id));
        }

        $zip->extractTo($staging);
        $zip->close();
        $this->filesystem->remove($archive);
    }

    /**
     * @param list<string> $command
     */
    private function run(array $command): string
    {
        $process = new Process($command, timeout: 600.0);

        if (0 !== $process->run()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}

/**
 * @param list<string> $arguments
 */
function main(array $arguments): int
{
    if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
        echo USAGE, "\n";

        return 0;
    }

    $force = in_array('--force', $arguments, true);
    $check = in_array('--check', $arguments, true);
    $unknown = array_values(array_diff($arguments, ['--force', '--check']));

    if ([] !== $unknown) {
        fwrite(STDERR, sprintf("Unknown option: %s\n\n%s\n", implode(', ', $unknown), USAGE));

        return 1;
    }

    try {
        $manifest = CorpusManifest::default();
    } catch (Throwable $e) {
        fwrite(STDERR, sprintf("The corpus manifest is not usable: %s\n", $e->getMessage()));

        return 1;
    }

    if ($check) {
        $stale = array_filter($manifest->entries(), static fn (CorpusEntry $entry): bool => ! $entry->isFetched());

        foreach ($stale as $entry) {
            fwrite(STDERR, sprintf(
                "The \"%s\" conformance corpus is missing or stale. Run \"composer conformance:fetch\".\n",
                $entry->id,
            ));
        }

        return [] === $stale ? 0 : 1;
    }

    $fetcher = new CorpusFetcher(sys_get_temp_dir() . '/solidinvoice-conformance-corpus');

    foreach ($manifest->entries() as $entry) {
        if (! $force && $entry->isFetched()) {
            printf("%-14s up to date (%s %s)\n", $entry->id, $entry->kind->value, $entry->ref);

            continue;
        }

        printf("%-14s fetching %s at %s ...\n", $entry->id, $entry->url, $entry->ref);

        try {
            $fetcher->fetch($entry);
        } catch (Throwable $e) {
            fwrite(STDERR, sprintf("%-14s FAILED: %s\n", $entry->id, $e->getMessage()));

            return 1;
        }

        printf("%-14s fetched at %s (%s)\n", $entry->id, $entry->pin, $entry->licence);
    }

    return 0;
}

exit(main(array_values(array_slice($argv, 1))));
