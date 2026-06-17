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
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements ProcessorInterface<Quote, Quote> */
final readonly class QuoteTransitionProcessor implements ProcessorInterface
{
    public function __construct(
        private WorkflowInterface $quoteStateMachine,
        private ManagerRegistry $registry,
        private EmailVerificationGateInterface $emailVerificationGate,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Quote
    {
        assert($data instanceof Quote);

        // Defence-in-depth behind the API authentication block (VerifiedUserChecker):
        // the "send" transition emails the quote to the client, so an unverified hosted
        // user must not be able to drive the quote workflow via the API.
        if ($this->emailVerificationGate->isGated()) {
            throw new AccessDeniedHttpException($this->emailVerificationGate->reason('send this quote'));
        }

        $transition = (string) ($context['request']?->attributes->get('transition') ?? '');

        if (! $this->quoteStateMachine->can($data, $transition)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Transition "%s" cannot be applied to quote in status "%s".', $transition, $data->getStatus()?->value ?? 'unknown')
            );
        }

        $this->quoteStateMachine->apply($data, $transition);
        $this->registry->getManager()->flush();

        return $data;
    }
}
