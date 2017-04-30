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

namespace CSBill\ClientBundle\Form\Handler;

use SolidWorx\FormHandler\FormCollectionHandlerInterface;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;

class ContactEditFormHandler extends ContactAddFormHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormCollectionHandlerInterface, FormHandlerSuccessInterface
{
    protected function getTemplate(): string
    {
        return '@CSBillClient/Ajax/contact_edit.html.twig';
    }
}
