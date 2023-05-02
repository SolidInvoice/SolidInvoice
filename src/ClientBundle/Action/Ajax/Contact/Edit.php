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

namespace SolidInvoice\ClientBundle\Action\Ajax\Contact;

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Handler\ContactEditFormHandler;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

final class Edit implements AjaxResponse
{
    public function __construct(private readonly FormHandler $handler)
    {
    }

    public function __invoke(Request $request, Contact $contact)
    {
        return $this->handler->handle(ContactEditFormHandler::class, ['contact' => $contact]);
    }
}
