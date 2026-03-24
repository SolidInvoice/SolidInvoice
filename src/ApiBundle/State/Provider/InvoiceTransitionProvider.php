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

namespace SolidInvoice\ApiBundle\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<Invoice> */
final class InvoiceTransitionProvider implements ProviderInterface
{
    public function __construct(
        private readonly InvoiceRepository $repository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Invoice
    {
        $invoice = $this->repository->findOneBy(['id' => $uriVariables['id']]);

        if (! $invoice instanceof Invoice) {
            throw new NotFoundHttpException(sprintf('Invoice "%s" not found.', $uriVariables['id']));
        }

        return $invoice;
    }
}
