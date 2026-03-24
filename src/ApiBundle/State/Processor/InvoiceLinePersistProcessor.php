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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<Line, Line> */
final class InvoiceLinePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Line
    {
        assert($data instanceof Line);

        $invoiceId = $uriVariables['invoiceId'] ?? null;
        $invoice = $this->invoiceRepository->find($invoiceId);

        if ($invoice === null) {
            throw new NotFoundHttpException(sprintf('Invoice "%s" not found.', $invoiceId));
        }

        $data->setInvoice($invoice);

        $em = $this->registry->getManager();
        $em->persist($data);
        $em->flush();

        return $data;
    }
}
