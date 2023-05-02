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

use SolidInvoice\ClientBundle\Form\Handler\ClientCreateFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;

final class Add
{
    public function __construct(private readonly FormHandler $handler)
    {
    }

    public function __invoke(Request $request): FormRequest
    {
        return $this->handler->handle(ClientCreateFormHandler::class);
    }
}
