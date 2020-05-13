<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
    public function testResponseInline()
    {
        $response = new PdfResponse('PDF Content', 'filename.pdf');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('inline; filename="filename.pdf"', $response->headers->get('Content-Disposition'));
    }

    public function testResponseDownload()
    {
        $response = new PdfResponse('PDF Content', 'filename.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="filename.pdf"', $response->headers->get('Content-Disposition'));
    }
}
