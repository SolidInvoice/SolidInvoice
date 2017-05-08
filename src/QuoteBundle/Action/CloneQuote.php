<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Action;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Manager\QuoteManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class CloneQuote
{
    /**
     * @var QuoteManager
     */
    private $manager;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(QuoteManager $manager, RouterInterface $router)
    {
        $this->manager = $manager;
        $this->router = $router;
    }

    public function __invoke(Request $request, Quote $quote)
    {
        $newQuote = $this->manager->duplicate($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $newQuote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.clone.success';
            }
        };
    }
}
