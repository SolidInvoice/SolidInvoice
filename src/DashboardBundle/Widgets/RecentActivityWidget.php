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

use Brick\Math\Exception\MathException;
use SolidInvoice\DashboardBundle\Activity\ActivityFeed;

/**
 * @see \SolidInvoice\DashboardBundle\Tests\Widgets\RecentActivityWidgetTest
 */
final readonly class RecentActivityWidget implements WidgetInterface
{
    /**
     * The types the widget template renders.
     */
    private const array TYPES = [
        ActivityFeed::PAYMENT,
        ActivityFeed::INVOICE_SENT,
        ActivityFeed::QUOTE_ACCEPTED,
        ActivityFeed::QUOTE_DECLINED,
        ActivityFeed::RECURRING_GENERATED,
    ];

    public function __construct(
        private ActivityFeed $feed,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MathException
     */
    public function getData(): array
    {
        $activities = $this->feed->entries(10, types: self::TYPES);

        return [
            'activities' => $activities,
            'hasActivities' => $activities !== [],
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/recent_activity.html.twig';
    }
}
