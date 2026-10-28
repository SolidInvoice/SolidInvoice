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

namespace SolidInvoice\CoreBundle\Company;

use const IMAGETYPE_JPEG;
use const IMAGETYPE_PNG;
use const IMAGETYPE_WEBP;
use GdImage;
use InvalidArgumentException;
use SolidInvoice\SettingsBundle\SystemConfig;
use function base64_decode;
use function base64_encode;
use function explode;
use function getimagesize;
use function imagealphablending;
use function imagecopyresampled;
use function imagecreatefromstring;
use function imagecreatetruecolor;
use function imagejpeg;
use function imagepng;
use function imagesavealpha;
use function imagesx;
use function imagesy;
use function in_array;
use function max;
use function min;
use function ob_get_clean;
use function ob_start;
use function round;
use function strlen;

/**
 * The company logo, stored as the `system/company/logo` setting (`<extension>|<base64>`), the way the
 * settings form stores it.
 */
final readonly class CompanyLogo
{
    public const string SETTING = 'system/company/logo';

    public const int MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Larger than any place it is shown (the PDFs draw it at 50 px or less), so it stays sharp on
     * high-density screens and in print.
     */
    private const int MAX_SIZE = 512;

    private const array ALLOWED_TYPES = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];

    public function __construct(
        private SystemConfig $config,
    ) {
    }

    /**
     * @return array{mimeType: string, content: string}|null
     */
    public function get(): ?array
    {
        $value = $this->config->get(self::SETTING);

        if ($value === null || $value === '') {
            return null;
        }

        [$extension, $data] = explode('|', $value, 2) + [1 => ''];

        return [
            'mimeType' => 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension),
            'content' => (string) base64_decode($data, true),
        ];
    }

    /**
     * Re-encodes the image, which also drops its EXIF data (location, camera), and scales it down to
     * at most 512 px on its longest side.
     *
     * @throws InvalidArgumentException when the content is not a PNG, JPEG or WebP image, or too large
     */
    public function store(string $content): void
    {
        if ($content === '' || strlen($content) > self::MAX_BYTES) {
            throw new InvalidArgumentException('The logo must be an image of at most 5 MB.');
        }

        $info = @getimagesize('data://application/octet-stream;base64,' . base64_encode($content));
        $image = $info !== false && in_array($info[2], self::ALLOWED_TYPES, true) ? @imagecreatefromstring($content) : false;

        if (! $image instanceof GdImage) {
            throw new InvalidArgumentException('The logo must be a PNG, JPEG or WebP image.');
        }

        $image = $this->scaleDown($image);

        ob_start();
        // A JPEG has no transparency to keep, and stays much smaller as a JPEG.
        $info[2] === IMAGETYPE_JPEG ? imagejpeg($image, null, 90) : imagepng($image, null, 9);
        $encoded = (string) ob_get_clean();

        $this->config->set(self::SETTING, ($info[2] === IMAGETYPE_JPEG ? 'jpeg' : 'png') . '|' . base64_encode($encoded));
    }

    public function remove(): void
    {
        $this->config->set(self::SETTING, null);
    }

    private function scaleDown(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIZE / max($width, $height));

        $scaled = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, imagesx($scaled), imagesy($scaled), $width, $height);

        return $scaled;
    }
}
