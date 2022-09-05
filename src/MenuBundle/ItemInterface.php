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

use Knp\Menu\ItemInterface as BaseInterface;

interface ItemInterface extends BaseInterface
{
    /**
     * @param ItemInterface|string|array $child
     *
     * @return ItemInterface
     */
    public function addChild($child, array $options = []);

    /**
     * @return $this
     */
    public function addDivider(string $type = '');

    /**
     * @return $this
     */
    public function addHeader(string $header);

    public function isDivider(): bool;
}
