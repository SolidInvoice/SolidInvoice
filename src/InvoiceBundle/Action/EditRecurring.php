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

namespace SolidInvoice\InvoiceBundle\Action;

use Money\Currency;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceEditHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

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
