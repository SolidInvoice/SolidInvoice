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
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements ProcessorInterface<RecurringInvoice, RecurringInvoice> */
final class RecurringInvoiceTransitionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly WorkflowInterface $recurringInvoiceStateMachine,
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RecurringInvoice
    {
        assert($data instanceof RecurringInvoice);

        $transition = (string) ($context['request']?->attributes->get('transition') ?? '');

        if (! $this->recurringInvoiceStateMachine->can($data, $transition)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Transition "%s" cannot be applied to recurring invoice in status "%s".', $transition, $data->getStatus()?->value ?? 'unknown')
            );
        }

        $this->recurringInvoiceStateMachine->apply($data, $transition);
        $this->registry->getManager()->flush();

        return $data;
    }
}
