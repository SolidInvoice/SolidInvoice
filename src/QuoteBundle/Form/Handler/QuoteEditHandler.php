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

namespace SolidInvoice\QuoteBundle\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Form\Handler\QuoteEditHandlerTest
 */
class QuoteEditHandler extends AbstractQuoteHandler
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceQuote/Default/edit.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'quote' => $formRequest->getOptions()->get('quote'),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess(FormRequest $form, $quote): ?Response
    {
        /* @var RedirectResponse $response */
        $response = parent::onSuccess($form, $quote);

        return new class($response->getTargetUrl()) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.action.edit.success';
            }
        };
    }
}
