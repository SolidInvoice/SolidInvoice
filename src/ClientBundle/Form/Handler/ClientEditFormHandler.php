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

namespace SolidInvoice\ClientBundle\Form\Handler;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidWorx\FormHandler\FormRequest;

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
