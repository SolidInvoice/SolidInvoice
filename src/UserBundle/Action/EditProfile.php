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

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\UserBundle\Form\Handler\ProfileEditFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;

final class EditProfile
{
    public function __invoke(FormHandler $formHandler): FormRequest
    {
        return $formHandler->handle(ProfileEditFormHandler::class);
    }
}
