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

namespace SolidInvoice\DashboardBundle;

use Exception;
use SolidInvoice\DashboardBundle\Widgets\WidgetInterface;
use SplPriorityQueue;

/**
 * @see \SolidInvoice\DashboardBundle\Tests\WidgetFactoryTest
 */
class WidgetFactory
{
    final public const DEFAULT_LOCATION = 'top';

    /**
     * @var SplPriorityQueue<int, WidgetInterface>[]
     */
    private array $queues = [];

    /**
     * @var string[]
     */
    private array $locations = ['top', 'left_column', 'right_column'];

    public function __construct()
    {
        foreach ($this->locations as $location) {
            $this->queues[$location] = new SplPriorityQueue();
        }
    }

    /**
     * @throws Exception
     */
    public function add(WidgetInterface $widget, string $location = null, int $priority = null): void
    {
        $location = $location ?: self::DEFAULT_LOCATION;

        if (! isset($this->queues[$location])) {
            throw new Exception(sprintf('Invalid widget location: %s', $location));
        }

        $this->queues[$location]->insert($widget, $priority);
    }

    /**
     * @return SplPriorityQueue<int, WidgetInterface>
     */
    public function get(string $location): SplPriorityQueue
    {
        return $this->queues[$location] ?? new SplPriorityQueue();
    }
}
