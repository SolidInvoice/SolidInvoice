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

namespace SolidInvoice\QuoteBundle\Message\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Message\ExpireQuotes;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Message\Handler\ExpireQuotesHandlerTest
 */
#[AsMessageHandler]
final readonly class ExpireQuotesHandler
{
    public function __construct(
        private QuoteRepository $quoteRepository,
        private WorkflowInterface $quoteStateMachine,
        private EntityManagerInterface $entityManager,
        private CompanySelector $companySelector,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExpireQuotes $message): void
    {
        $this->companySelector->switchCompany($message->companyId);

        try {
            foreach ($this->quoteRepository->getIdsOfQuotesToExpire($this->clock->now()) as $id) {
                $quote = $this->quoteRepository->find($id);

                // The quote may have been accepted or edited since the IDs were read.
                if ($quote instanceof Quote && $this->quoteStateMachine->can($quote, Graph::TRANSITION_EXPIRE)) {
                    // WorkFlowSubscriber flushes and sends the status notification.
                    $this->quoteStateMachine->apply($quote, Graph::TRANSITION_EXPIRE);

                    $this->logger->info('Quote expired', ['quote_id' => $id->toString()]);
                }

                // Keeps memory flat for a company with many quotes to expire.
                $this->entityManager->clear();
            }
        } finally {
            $this->companySelector->reset();
        }
    }
}
