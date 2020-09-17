<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action;

use Money\Currency;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceEditHandler;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class EditRecurring
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var FormHandler
     */
    private $formHandler;

    public function __construct(FormHandler $formHandler, Currency $currency)
    {
        $this->currency = $currency;
        $this->formHandler = $formHandler;
    }

    public function __invoke(Request $request, RecurringInvoice $invoice)
    {
        $options = [
            'invoice' => $invoice,
            'recurring' => true,
            'form_options' => [
                'currency' => $invoice->getClient()->getCurrency() ?: $this->currency,
            ],
        ];

        return $this->formHandler->handle(InvoiceEditHandler::class, $options);
    }
}
