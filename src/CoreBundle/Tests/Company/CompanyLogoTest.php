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

namespace SolidInvoice\CoreBundle\Tests\Company;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanyLogo;
use SolidInvoice\SettingsBundle\SystemConfig;
use function crc32;
use function gzcompress;
use function pack;
use function str_repeat;
use function strlen;

final class CompanyLogoTest extends TestCase
{
    public function testAnImageThatWouldDecodeToAHugeBitmapIsRefusedBeforeDecoding(): void
    {
        // A valid 1-bit PNG of 6400 x 6400 px: a few KB on disk, 41 megapixels once decoded.
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $row = "\0" . str_repeat("\0", 800);
        $png = "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', 6400, 6400, 1, 0, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress(str_repeat($row, 6400)))
            . $chunk('IEND', '');

        $config = $this->createMock(SystemConfig::class);
        $config->expects(self::never())->method('set');

        $this->expectException(InvalidArgumentException::class);

        new CompanyLogo($config)->store($png);
    }
}
