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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Twig\Extension\FileExtension;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class FileExtensionTest extends TestCase
{
    private string $projectDir;

    private string $publicDir;

    private string $secretFile;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/solidinvoice-file-extension-' . uniqid('', true);
        $this->publicDir = $this->projectDir . '/public';

        mkdir($this->publicDir . '/static', 0o777, true);

        file_put_contents($this->publicDir . '/static/pdf.css', 'body { color: red; }');
        file_put_contents($this->publicDir . '/static/style.css', 'body { color: blue; }');

        // A file outside the public directory which the function must never reach.
        $this->secretFile = $this->projectDir . '/secret.env';
        file_put_contents($this->secretFile, 'DB_PASSWORD=super-secret');
    }

    protected function tearDown(): void
    {
        @unlink($this->publicDir . '/static/pdf.css');
        @unlink($this->publicDir . '/static/style.css');
        @unlink($this->secretFile);
        @rmdir($this->publicDir . '/static');
        @rmdir($this->publicDir);
        @rmdir($this->projectDir);
    }

    public function testReadsLegitimateFileUnderPublic(): void
    {
        $extension = new FileExtension($this->projectDir);

        self::assertSame('body { color: red; }', $extension->readFile('static/pdf.css'));
        self::assertSame('body { color: blue; }', $extension->readFile('/static/style.css'));
        self::assertSame('body { color: blue; }', $extension->readFile('\\static/style.css'));
    }

    public function testRejectsParentDirectoryTraversal(): void
    {
        $extension = new FileExtension($this->projectDir);

        self::assertSame('', $extension->readFile('../secret.env'));
        self::assertSame('', $extension->readFile('../../etc/passwd'));
        self::assertSame('', $extension->readFile('static/../../secret.env'));
    }

    public function testReturnsEmptyStringForMissingFile(): void
    {
        $extension = new FileExtension($this->projectDir);

        self::assertSame('', $extension->readFile('static/does-not-exist.css'));
    }

    public function testReturnsEmptyStringWhenPublicDirIsMissing(): void
    {
        $extension = new FileExtension(sys_get_temp_dir() . '/solidinvoice-missing-' . uniqid('', true));

        self::assertSame('', $extension->readFile('static/pdf.css'));
    }
}
