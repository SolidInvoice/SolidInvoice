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
use CSBill\InvoiceBundle\Form\Handler\InvoiceEditHandler;
use CSBill\InvoiceBundle\Model\Graph;
use Money\Currency;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class Edit
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var FormHandler
     */
    private $formHandler;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router, FormHandler $formHandler, Currency $currency)
    {
        $this->router = $router;
        $this->currency = $currency;
        $this->formHandler = $formHandler;
    }

    public function __invoke(Request $request, Invoice $invoice)
    {
        if ($invoice->getStatus() === Graph::STATUS_PAID) {
            $route = $this->router->generate('_invoices_index');

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): iterable
                {
                    yield FlashResponse::FLASH_WARNING => 'invoice.edit.paid';
                }
            };
        }

        $currency = $invoice->getClient()->getCurrency() ?: $this->currency;

        return $this->formHandler->handle(InvoiceEditHandler::class, $invoice, ['currency' => $currency]);
    }
}
