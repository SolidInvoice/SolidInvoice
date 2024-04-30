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

namespace SolidInvoice\InvoiceBundle\Message\Handler;

use Brick\Math\Exception\MathException;
use JsonException;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Workflow\StateMachine;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Message\Handler\CreateInvoiceFromRecurringHandlerTest
 */
final class CreateInvoiceFromRecurringHandler implements MessageSubscriberInterface
{
    public function __construct(
        private readonly InvoiceManager $invoiceManager,
        private readonly StateMachine $invoiceStateMachine,
        private readonly CompanySelector $companySelector,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return iterable<string, array<string, string>>
     */
    public static function getHandledMessages(): iterable
    {
        yield CreateInvoiceFromRecurring::class => [
            'from_transport' => 'sync',
        ];
    }

    public function __invoke(CreateInvoiceFromRecurring $message): void
    {
        $invoice = $message->getRecurringInvoice();

        $this->companySelector->switchCompany($invoice->getCompany()->getId());

        try {
            $newInvoice = $this->invoiceManager->createFromRecurring($invoice);
            $this->invoiceManager->create($newInvoice);
            $this->invoiceStateMachine->apply($newInvoice, Graph::TRANSITION_ACCEPT);
        } catch (MathException | InvalidTransitionException | JsonException $e) {
            $this->logger->error('An error occurred while creating invoice from recurring', ['exception' => $e]);
        }
    }
}
