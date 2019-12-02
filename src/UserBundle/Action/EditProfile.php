<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\UserBundle\Form\Handler\ProfileEditFormHandler;
use SolidWorx\FormHandler\FormHandler;

final class EditProfile
{
    public function __invoke(FormHandler $formHandler)
    {
        return $formHandler->handle(ProfileEditFormHandler::class);
    }
}
