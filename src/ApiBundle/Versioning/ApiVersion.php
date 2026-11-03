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

namespace SolidInvoice\ApiBundle\Versioning;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use function sprintf;
use function Symfony\Component\Clock\now;

/**
 * The API versions a client can opt into with the `Api-Version` request header. A request
 * without the header gets the original wire format, which is deprecated and announced as such
 * on every response.
 *
 * @see \SolidInvoice\ApiBundle\Tests\Versioning\ApiVersionTest
 */
enum ApiVersion: string
{
    /**
     * Money as decimal strings in major units, at the currency's own precision.
     */
    case V2026_10 = '2026-10';

    final public const string HEADER = 'Api-Version';

    /**
     * Serializer context flag set when the request opted into decimal money.
     */
    final public const string DECIMAL_MONEY = 'api_decimal_money';

    /**
     * When the original wire format stops being served (RFC 8594 `Sunset`).
     */
    final public const string LEGACY_SUNSET = 'Sat, 01 May 2027 00:00:00 GMT';

    /**
     * When the original wire format was deprecated, as an RFC 9745 `Deprecation` date (2026-11-01).
     */
    final public const string LEGACY_DEPRECATION = '@1793491200';

    /**
     * Where the deprecation is explained, sent as the `Link` with `rel="deprecation"`.
     */
    final public const string LEGACY_DOCS = 'https://solidinvoice.co/docs/api/rest-api#money-format-and-api-versions';

    /**
     * Once past, a request without the header gets the latest version instead of the original format.
     */
    public static function isLegacySunset(): bool
    {
        return now() >= new DateTimeImmutable(self::LEGACY_SUNSET);
    }

    /**
     * Null when the request did not ask for a version.
     */
    public static function fromRequest(Request $request): ?self
    {
        $header = $request->headers->get(self::HEADER);

        if ($header === null || $header === '') {
            return null;
        }

        return self::tryFrom($header) ?? throw new BadRequestHttpException(sprintf('Unsupported %s "%s". Supported: %s.', self::HEADER, $header, self::V2026_10->value));
    }
}
