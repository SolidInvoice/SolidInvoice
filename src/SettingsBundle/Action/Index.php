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

namespace SolidInvoice\SettingsBundle\Action;

use SolidInvoice\SettingsBundle\Form\Handler\SettingsFormHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

final class Index
{
    public function __construct(private readonly FormHandler $handler)
    {
    }

    public function __invoke(Request $request)
    {
        return $this->handler->handle(SettingsFormHandler::class);
    }
}
