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

namespace SolidInvoice\CoreBundle\Templates;

enum BillingTemplateChannel: string
{
    case Pdf = 'pdf';
    case Email = 'email';

    /**
     * Browser-rendered document body, used on the internal and external
     * (view-by-uuid) pages as well as the settings-page preview.
     */
    case View = 'view';

    public function fileName(): string
    {
        return match ($this) {
            self::Pdf => 'pdf.html.twig',
            self::Email => 'email.html.twig',
            self::View => 'preview.html.twig',
        };
    }
}
