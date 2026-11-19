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

use Symfony\Component\Uid\Ulid;

/**
 * Stores and retrieves attachment blobs behind a storage key, so the backend
 * (local filesystem today, object storage later) stays swappable without
 * touching a call site.
 */
interface AttachmentStorageInterface
{
    /**
     * Writes the file at $sourcePath into storage and returns the generated
     * storage key. The key is derived from $companyId and $attachmentId only —
     * never from the uploaded filename or any other request value.
     */
    public function write(Ulid $companyId, Ulid $attachmentId, string $sourcePath): string;

    /**
     * Opens the stored blob for reading.
     *
     * @return resource
     */
    public function stream(string $storageKey);

    /**
     * Removes the stored blob. A no-op when $storageKey does not exist, so
     * cleanup stays idempotent.
     */
    public function delete(string $storageKey): void;

    public function exists(string $storageKey): bool;
}
