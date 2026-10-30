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

namespace SolidInvoice\CoreBundle\Logger;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use function json_decode;

/**
 * frankenphp_log() only accepts scalars and arrays, and throws on anything else — failing the request.
 * NormalizerFormatter leaves objects nested inside a JsonSerializable's output untouched (the MCP SDK
 * logs every request that way), so round-trip the normalized record through JSON to flatten them.
 */
final class FrankenPhpFormatter extends NormalizerFormatter
{
    /**
     * @return array<mixed>
     */
    public function format(LogRecord $record): array
    {
        return (array) json_decode($this->toJson(parent::format($record), true), true);
    }
}
