<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Handler\UserEditFormHandler;
use SolidWorx\FormHandler\FormHandler;

final class EditUser
{
    /**
     * @var FormHandler
     */
    private $formHandler;

    public function __construct(FormHandler $formHandler)
    {
        $this->formHandler = $formHandler;
    }

    public function __invoke(User $user)
    {
        return $this->formHandler->handle(UserEditFormHandler::class, ['user' => $user]);
    }
}