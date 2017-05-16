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

namespace CSBill\QuoteBundle\Action\Transition;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Manager\QuoteManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class Send
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
        $this->manager->send($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse
        {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.transition.action.sent';
            }
        };
    }
}
