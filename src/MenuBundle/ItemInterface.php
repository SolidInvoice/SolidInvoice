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
    public function addDivider(string $type = ''): self;

    public function addHeader(string $header): self;

    public function isDivider(): bool;
}
