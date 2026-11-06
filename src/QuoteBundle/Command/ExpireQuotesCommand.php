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

namespace SolidInvoice\QuoteBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use SolidInvoice\QuoteBundle\Message\ExpireQuotes;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use function count;
use function sprintf;

/**
 * A quote is valid through its `due` date, so it expires on the first run after that day ends.
 * Comparing with `<` rather than `=` means a missed run catches up on the next one.
 *
 * @see \SolidInvoice\QuoteBundle\Tests\Command\ExpireQuotesCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:quotes:expire',
    description: 'Expire pending quotes whose validity date has passed',
)]
#[AsCronTask('#midnight', schedule: 'expire_quotes')]
final class ExpireQuotesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuoteRepository $quoteRepository,
        private readonly MessageBusInterface $bus,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $filters = $this->entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        try {
            $companyIds = $this->quoteRepository->getCompanyIdsWithQuotesToExpire($this->clock->now());
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        // One message per company: the handler runs in that company's context, and a failure
        // in one tenant is retried without redoing the others.
        foreach ($companyIds as $companyId) {
            $this->bus->dispatch(new ExpireQuotes($companyId));
        }

        $this->io->success(sprintf('Dispatched quote expiry for %d companies.', count($companyIds)));

        return self::SUCCESS;
    }
}
