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

/**
 * API Platform's Doctrine item provider loads `$data` before this runs, under `CompanyFilter` —
 * a foreign document is a 404 before this body executes, so there is no tenant check to do here.
 *
 * @see \SolidInvoice\EInvoiceBundle\Tests\Functional\Api\EInvoiceDocumentTest
 */
final readonly class DownloadPayload
{
    public function __invoke(EInvoiceDocument $data): Response
    {
        $response = new Response($data->getPayload(), Response::HTTP_OK, [
            'Content-Type' => $data->getPayloadMediaType(),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $data->getPayloadFilename(),
                'einvoice.xml',
            ),
        ]);

        return $response->setEtag($data->getPayloadHash());
    }
}
