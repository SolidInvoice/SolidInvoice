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

use Exception;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceEditHandler;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;

final class EditRecurring
{
    private FormHandler $formHandler;

    private SystemConfig $systemConfig;

    public function __construct(FormHandler $formHandler, SystemConfig $systemConfig)
    {
        $this->formHandler = $formHandler;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @throws Exception
     */
    public function __invoke(Request $request, RecurringInvoice $invoice): FormRequest
    {
        $options = [
            'invoice' => $invoice,
            'recurring' => true,
            'form_options' => [
                'currency' => $invoice->getClient()->getCurrency() ?: $this->systemConfig->getCurrency(),
            ],
        ];

        return $this->formHandler->handle(InvoiceEditHandler::class, $options);
    }
}
