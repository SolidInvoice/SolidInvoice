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

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\UserBundle\Form\Handler\UserAddFormHandler;
use SolidWorx\FormHandler\FormHandler;

final class AddUser
{
    /**
     * @var FormHandler
     */
    private $formHandler;

    public function __construct(FormHandler $formHandler)
    {
        $this->formHandler = $formHandler;
    }

    public function __invoke()
    {
        return $this->formHandler->handle(UserAddFormHandler::class);
    }
}