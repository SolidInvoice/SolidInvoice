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

namespace CSBill\InvoiceBundle\Action;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\InvoiceBundle\Model\Graph;
use Finite\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class Transition
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var InvoiceManager
     */
    private $manager;

    public function __construct(RouterInterface $router, InvoiceManager $manager, FactoryInterface $factory)
    {
        $this->router = $router;
        $this->manager = $manager;
        $this->factory = $factory;
    }

    public function __invoke(Request $request, string $action, Invoice $invoice)
    {
        if (!$this->factory->get($invoice, Graph::GRAPH)->can($action)) {
            throw new InvalidTransitionException($action);
        }

        $this->manager->$action($invoice);

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

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
                yield self::FLASH_SUCCESS => 'invoice.transition.action.'.$this->action;
            }
        };
    }
}
