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

namespace SolidInvoice\EInvoiceBundle\Action\Api;

use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * API Platform's Doctrine item provider loads `$data` before this runs, under `CompanyFilter` —
 * a foreign document is a 404 before this body executes, so there is no tenant check to do here.
 *
 * The `Content-Type` is `application/octet-stream` until #2679 adds `receipt_media_type` /
 * `receipt_filename` to the entity — guessing `application/xml` would be wrong for every
 * JSON-receipt platform (`design` §5.4 on SOL-94).
 *
 * @see \SolidInvoice\EInvoiceBundle\Tests\Functional\Api\EInvoiceDocumentTest
 */
final readonly class DownloadReceipt
{
    public function __invoke(EInvoiceDocument $data): Response
    {
        $receipt = $data->getReceipt();

        if ($receipt === null) {
            throw new NotFoundHttpException('This document has not been cleared yet; it has no receipt.');
        }

        return new Response($receipt, Response::HTTP_OK, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $data->getSourceNumber() . '-receipt',
                'receipt',
            ),
        ]);
    }
}
