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

namespace SolidInvoice\ClientBundle\Action;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Handler\ClientEditFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;

final class Edit
{
    public function __construct(private readonly FormHandler $handler)
    {
    }

    public function __invoke(Request $request, Client $client): FormRequest
    {
        return $this->handler->handle(ClientEditFormHandler::class, ['client' => $client]);
    }
}
