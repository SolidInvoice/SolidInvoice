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

namespace SolidInvoice\InvoiceBundle\Action;

use Symfony\Bridge\Twig\Attribute\Template;

final class ReceiptIndex
{
    /**
     * @return array{}
     */
    #[Template('@SolidInvoiceInvoice/Receipt/index.html.twig')]
    public function __invoke(): array
    {
        return [];
    }
}
