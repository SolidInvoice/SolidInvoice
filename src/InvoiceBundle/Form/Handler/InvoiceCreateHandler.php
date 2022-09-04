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

use SolidInvoice\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Handler\InvoiceCreateHandlerTest
 */
class InvoiceCreateHandler extends AbstractInvoiceHandler
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceInvoice/Default/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'recurring' => $formRequest->getOptions()->get('recurring'),
            ]
        );
    }
}
