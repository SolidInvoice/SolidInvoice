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

use SolidInvoice\UserBundle\Form\Handler\RegisterFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Register
{
    public function __invoke(ToggleInterface $toggle, FormHandler $formHandler): FormRequest
    {
        if (!$toggle->isActive('allow_registration')) {
            throw new NotFoundHttpException('Registration is disabled');
        }

        return $formHandler->handle(RegisterFormHandler::class);
    }
}
