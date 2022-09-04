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

namespace SolidInvoice\ClientBundle\Form\Handler;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Handler\ClientEditFormHandlerTest
 */
class ClientEditFormHandler extends AbstractClientFormHandler
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest): Template
    {
        return new Template(
            '@SolidInvoiceClient/Default/edit.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'client' => $formRequest->getOptions()->get('client'),
            ]
        );
    }
}
