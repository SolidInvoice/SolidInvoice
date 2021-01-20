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

namespace SolidInvoice\DashboardBundle\Widgets;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

class RecentQuotesWidget implements WidgetInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    public function getData(): array
    {
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository(Quote::class);

        $quotes = $quoteRepository->getRecentQuotes();

        return ['quotes' => $quotes];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/recent_quotes.html.twig';
    }
}
