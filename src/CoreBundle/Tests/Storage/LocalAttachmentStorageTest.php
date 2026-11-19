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

namespace SolidInvoice\CoreBundle\Tests\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\CoreBundle\Storage\LocalAttachmentStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Ulid;
use function basename;
use function bin2hex;
use function dirname;
use function fclose;
use function random_bytes;
use function stream_get_contents;
use function sys_get_temp_dir;

#[CoversClass(LocalAttachmentStorage::class)]
final class LocalAttachmentStorageTest extends TestCase
{
    private Filesystem $filesystem;

    private string $attachmentsDir;

    private LocalAttachmentStorage $storage;

    private ?string $outsideFile = null;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->attachmentsDir = sys_get_temp_dir() . '/solidinvoice_attachments_test_' . bin2hex(random_bytes(8));
        $this->storage = new LocalAttachmentStorage($this->filesystem, $this->attachmentsDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->attachmentsDir);

        if ($this->outsideFile !== null) {
            $this->filesystem->remove($this->outsideFile);
        }
    }

    public function testWriteGeneratesAKeyFromTheGivenUlidsWithNoExtension(): void
    {
        $companyId = new Ulid();
        $attachmentId = new Ulid();

        $storageKey = $this->storage->write($companyId, $attachmentId, $this->fixture('attachment contents'));

        self::assertSame($companyId->toBase58() . '/' . $attachmentId->toBase58(), $storageKey);
        self::assertStringNotContainsString('.', $storageKey);
    }

    public function testWriteThenExistsThenStreamThenDeleteRoundTrip(): void
    {
        $storageKey = $this->storage->write(new Ulid(), new Ulid(), $this->fixture('attachment contents'));

        self::assertTrue($this->storage->exists($storageKey));

        $resource = $this->storage->stream($storageKey);
        self::assertSame('attachment contents', stream_get_contents($resource));
        fclose($resource);

        $this->storage->delete($storageKey);

        self::assertFalse($this->storage->exists($storageKey));
    }

    public function testDeleteOnAnAbsentKeyIsANoOp(): void
    {
        $this->storage->delete('does-not/exist');

        self::assertFalse($this->storage->exists('does-not/exist'));
    }

    public function testExistsIsFalseForAMissingKey(): void
    {
        self::assertFalse($this->storage->exists('missing/key'));
    }

    public function testStreamThrowsForAMissingKey(): void
    {
        $this->expectException(RuntimeException::class);

        $this->storage->stream('missing/key');
    }

    public function testExistsReturnsFalseForATraversalKeyThatWouldEscapeTheRoot(): void
    {
        $outsideFile = $this->plantFileOutsideTheAttachmentRoot();

        self::assertFalse($this->storage->exists('../' . basename($outsideFile)));
    }

    public function testStreamThrowsForATraversalKeyThatWouldEscapeTheRoot(): void
    {
        $outsideFile = $this->plantFileOutsideTheAttachmentRoot();

        $this->expectException(RuntimeException::class);

        $this->storage->stream('../' . basename($outsideFile));
    }

    public function testDeleteDoesNotRemoveAFileOutsideTheRootForATraversalKey(): void
    {
        $outsideFile = $this->plantFileOutsideTheAttachmentRoot();

        $this->storage->delete('../' . basename($outsideFile));

        self::assertFileExists($outsideFile);
    }

    private function fixture(string $contents): string
    {
        $path = sys_get_temp_dir() . '/solidinvoice_attachment_fixture_' . bin2hex(random_bytes(8));
        $this->filesystem->dumpFile($path, $contents);

        return $path;
    }

    /**
     * Creates the attachment root (so realpath() on it succeeds) and plants a
     * file next to it, simulating what a crafted "../" storage key would try
     * to reach.
     */
    private function plantFileOutsideTheAttachmentRoot(): string
    {
        $this->filesystem->mkdir($this->attachmentsDir);

        $this->outsideFile = dirname($this->attachmentsDir) . '/solidinvoice_escaped_attachment_' . bin2hex(random_bytes(8));
        $this->filesystem->dumpFile($this->outsideFile, 'should not be reachable');

        return $this->outsideFile;
    }
}
