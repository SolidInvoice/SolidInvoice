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
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<RecurringInvoice> */
final class RecurringInvoiceItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly RecurringInvoiceRepository $repository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): RecurringInvoice
    {
        $recurringInvoice = $this->repository->findOneBy(['id' => $uriVariables['id']]);

        if (! $recurringInvoice instanceof RecurringInvoice) {
            throw new NotFoundHttpException(sprintf('Recurring invoice "%s" not found.', $uriVariables['id']));
        }

        return $recurringInvoice;
    }
}
