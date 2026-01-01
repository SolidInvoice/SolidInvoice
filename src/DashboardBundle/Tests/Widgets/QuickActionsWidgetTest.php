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

namespace SolidInvoice\DashboardBundle\Tests\Widgets;

use SolidInvoice\DashboardBundle\Widgets\QuickActionsWidget;

final class QuickActionsWidgetTest extends WidgetTestCase
{
    public function testGetDataReturnsEmptyArray(): void
    {
        $widget = self::getContainer()->get(QuickActionsWidget::class);

        $data = $widget->getData();

        self::assertSame([], $data);
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(QuickActionsWidget::class);

        self::assertSame('@SolidInvoiceDashboard/Widget/quick_actions.html.twig', $widget->getTemplate());
    }

    public function testRenderWidget(): void
    {
        $widget = self::getContainer()->get(QuickActionsWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
