<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\UserBundle\Action\Security;

use SolidInvoice\UserBundle\Form\Handler\PasswordChangeHandler;
use SolidWorx\FormHandler\FormHandler;

final class ChangePassword
{
    public function __invoke(FormHandler $formHandler)
    {
        return $formHandler->handle(PasswordChangeHandler::class);
    }
}
