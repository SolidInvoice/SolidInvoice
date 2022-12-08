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

namespace SolidInvoice\CoreBundle\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Response\PdfResponseTest
 */
class PdfResponse extends Response
{
    public function __construct(string $content, string $fileName, string $contentDisposition = ResponseHeaderBag::DISPOSITION_INLINE, int $status = Response::HTTP_OK, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
        $this->headers->add(['Content-Type' => 'application/pdf']);
        $this->headers->add(['Content-Disposition' => $this->headers->makeDisposition($contentDisposition, $fileName)]);
    }
}
