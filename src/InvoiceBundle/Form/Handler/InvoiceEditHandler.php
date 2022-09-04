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

namespace SolidInvoice\InvoiceBundle\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Handler\InvoiceEditHandlerTest
 */
class InvoiceEditHandler extends AbstractInvoiceHandler
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceInvoice/Default/edit.html.twig',
            [
                'recurring' => $formRequest->getOptions()->get('recurring'),
                'form' => $formRequest->getForm()->createView(),
                'invoice' => $formRequest->getOptions()->get('invoice'),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess(FormRequest $form, $invoice): ?Response
    {
        /* @var RedirectResponse $response */
        $response = parent::onSuccess($form, $invoice);

        return new class($response->getTargetUrl()) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'invoice.edit.success';
            }
        };
    }
}
