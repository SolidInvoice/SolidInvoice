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

namespace SolidInvoice\MenuBundle;

use Knp\Menu\Renderer\RendererInterface as BaseInterface;
use SolidInvoice\MenuBundle\Builder\MenuBuilder;
use SplPriorityQueue;

interface RendererInterface extends BaseInterface
{
    /**
     * Build and render a menu.
     *
     * @param SplPriorityQueue<int, MenuBuilder> $storage
     * @param array<string, mixed> $options
     */
    public function build(SplPriorityQueue $storage, array $options = []): string;
}
