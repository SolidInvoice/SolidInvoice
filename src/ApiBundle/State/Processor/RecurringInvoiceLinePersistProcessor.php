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
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<RecurringInvoiceLine, RecurringInvoiceLine> */
final class RecurringInvoiceLinePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RecurringInvoiceRepository $recurringInvoiceRepository,
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RecurringInvoiceLine
    {
        assert($data instanceof RecurringInvoiceLine);

        $invoiceId = $uriVariables['invoiceId'] ?? null;
        $recurringInvoice = $this->recurringInvoiceRepository->find($invoiceId);

        if ($recurringInvoice === null) {
            throw new NotFoundHttpException(sprintf('Recurring invoice "%s" not found.', $invoiceId));
        }

        $data->setRecurringInvoice($recurringInvoice);

        $em = $this->registry->getManager();
        $em->persist($data);
        $em->flush();

        return $data;
    }
}
