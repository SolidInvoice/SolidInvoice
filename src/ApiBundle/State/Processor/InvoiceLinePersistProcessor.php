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
final readonly class InvoiceLinePersistProcessor implements ProcessorInterface
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ManagerRegistry $registry,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Line
    {
        assert($data instanceof Line);

        $invoiceId = $uriVariables['invoiceId'] ?? null;
        // findOneBy() rather than find(): the CompanyFilter applies to both, but find() can
        // answer from the identity map without querying, and 0a27718dc left this lookup as the
        // only check between a posted line and its owner. See CompanyFilter's docblock.
        $invoice = $this->invoiceRepository->findOneBy(['id' => $invoiceId]);

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
