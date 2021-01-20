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

namespace SolidInvoice\ClientBundle\Form\Handler;

class ContactEditFormHandler extends AbstractContactFormHandler
{
    public function getTemplate(): string
    {
        return '@SolidInvoiceClient/Ajax/contact_edit.html.twig';
    }
}
