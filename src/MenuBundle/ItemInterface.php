<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle;

use Knp\Menu\ItemInterface as BaseInterface;

interface ItemInterface extends BaseInterface
{
    /**
     * {@inheritdoc}
     *
     * @return ItemInterface
     */
    public function addChild($child, array $options = []);

    /**
     * @param string $type
     *
     * @return $this
     */
    public function addDivider($type = '');

    /**
     * @return bool
     */
    public function isDivider();
}
