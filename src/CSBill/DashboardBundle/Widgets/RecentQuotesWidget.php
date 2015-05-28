<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\DashboardBundle\Widgets;

use CSBill\QuoteBundle\Repository\QuoteRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class RecentQuotesWidget implements WidgetInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array
     */
    public function getData()
    {
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository('CSBillQuoteBundle:Quote');

        $quotes = $quoteRepository->getRecentQuotes();

        return array('quotes' => $quotes);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'CSBillDashboardBundle:Widget:recent_quotes.html.twig';
    }
}
