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

namespace SolidInvoice\CoreBundle\Export\Enum;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Json = 'json';
    case Xml = 'xml';

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv',
            self::Json => 'application/json',
            self::Xml => 'application/xml',
        };
    }

    /**
     * Format string accepted by Symfony's Serializer encoders.
     */
    public function encoderFormat(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function encoderContext(string $xmlRootNode = 'export'): array
    {
        return match ($this) {
            self::Csv => [
                CsvEncoder::DELIMITER_KEY => ',',
                CsvEncoder::ENCLOSURE_KEY => '"',
                CsvEncoder::NO_HEADERS_KEY => false,
            ],
            self::Json => [
                'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ],
            self::Xml => [
                XmlEncoder::ROOT_NODE_NAME => $xmlRootNode,
                XmlEncoder::FORMAT_OUTPUT => true,
                XmlEncoder::ENCODING => 'UTF-8',
            ],
        };
    }
}
