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

namespace SolidInvoice\ApiBundle\Tests\Versioning;

use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ApiVersionTest extends TestCase
{
    public function testNoHeaderMeansNoVersion(): void
    {
        self::assertNull(ApiVersion::fromRequest(new Request()));
    }

    public function testKnownVersion(): void
    {
        $request = new Request();
        $request->headers->set(ApiVersion::HEADER, '2026-10');

        self::assertSame(ApiVersion::V2026_10, ApiVersion::fromRequest($request));
    }

    public function testUnknownVersionIsABadRequest(): void
    {
        $request = new Request();
        $request->headers->set(ApiVersion::HEADER, '2020-01');

        $this->expectException(BadRequestHttpException::class);

        ApiVersion::fromRequest($request);
    }
}
