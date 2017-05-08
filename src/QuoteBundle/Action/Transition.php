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
use Finite\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class Transition
{
    /**
     * @var QuoteManager
     */
    private $manager;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(QuoteManager $manager, RouterInterface $router, FactoryInterface $factory)
    {
        $this->manager = $manager;
        $this->factory = $factory;
        $this->router = $router;
    }

    public function __invoke(Request $request, string $action, Quote $quote)
    {
        $this->manager->$action($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        return new class($action, $route) extends RedirectResponse implements FlashResponse {
            /**
             * @var string
             */
            private $action;

            public function __construct(string $action, string $route)
            {
                $this->action = $action;
                parent::__construct($route);
            }

            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.transition.action.'.$this->action;
            }
        };
    }
}
