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

use SolidInvoice\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Form\Handler\QuoteCreateHandlerTest
 */
class QuoteCreateHandler extends AbstractQuoteHandler
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceQuote/Default/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }
}
