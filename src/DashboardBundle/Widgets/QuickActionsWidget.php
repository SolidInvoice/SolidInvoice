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

namespace SolidInvoice\DashboardBundle\Widgets;

final class QuickActionsWidget implements WidgetInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/quick_actions.html.twig';
    }
}
