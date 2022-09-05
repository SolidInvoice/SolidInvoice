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

namespace SolidInvoice\CoreBundle\Tests\Response;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PdfResponseTest extends TestCase
{
    public function testResponseInline(): void
    {
        $response = new PdfResponse('PDF Content', 'filename.pdf');

        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertSame('inline; filename=filename.pdf', $response->headers->get('Content-Disposition'));
    }

    public function testResponseDownload(): void
    {
        $response = new PdfResponse('PDF Content', 'filename.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename=filename.pdf', $response->headers->get('Content-Disposition'));
    }
}
