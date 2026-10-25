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

namespace SolidInvoice\CoreBundle\Storage;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Ulid;
use function realpath;
use function sprintf;
use function str_starts_with;

/**
 * Local filesystem implementation of AttachmentStorageInterface. Blobs live at
 * {$attachmentsDir}/{companyUlidBase58}/{attachmentUlidBase58} — no extension,
 * so a misconfigured server that somehow exposed the directory has nothing to
 * hand to an interpreter. The directory is created lazily on first write.
 * @see \SolidInvoice\CoreBundle\Tests\Storage\LocalAttachmentStorageTest
 */
final readonly class LocalAttachmentStorage implements AttachmentStorageInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private string $attachmentsDir,
    ) {
    }

    public function write(Ulid $companyId, Ulid $attachmentId, string $sourcePath): string
    {
        $storageKey = $companyId->toBase58() . '/' . $attachmentId->toBase58();

        // Filesystem::copy() creates the target directory itself, so the
        // company subdirectory needs no separate mkdir call.
        $this->filesystem->copy($sourcePath, $this->attachmentsDir . '/' . $storageKey, true);

        return $storageKey;
    }

    public function stream(string $storageKey)
    {
        $path = $this->resolveContainedPath($storageKey);
        if ($path === null) {
            throw new RuntimeException(sprintf('Attachment "%s" does not exist.', $storageKey));
        }

        $resource = fopen($path, 'rb');
        if ($resource === false) {
            throw new RuntimeException(sprintf('Could not open attachment "%s" for reading.', $storageKey));
        }

        return $resource;
    }

    public function delete(string $storageKey): void
    {
        $path = $this->resolveContainedPath($storageKey);
        if ($path === null) {
            return;
        }

        $this->filesystem->remove($path);
    }

    public function exists(string $storageKey): bool
    {
        return $this->resolveContainedPath($storageKey) !== null;
    }

    /**
     * Resolves a storage key to an absolute path, but only when that path
     * exists and sits inside the attachment root. Returns null for a missing
     * blob and for a key that would otherwise escape the root (e.g. one
     * containing "../"), so callers cannot tell the two cases apart.
     */
    private function resolveContainedPath(string $storageKey): ?string
    {
        $root = realpath($this->attachmentsDir);
        $resolved = realpath($this->attachmentsDir . '/' . $storageKey);

        if ($root === false || $resolved === false || ! str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $resolved;
    }
}
