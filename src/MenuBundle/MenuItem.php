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

use Knp\Menu\MenuItem as BaseItem;

class MenuItem extends BaseItem implements ItemInterface
{
    /**
     * @param \Knp\Menu\ItemInterface|string|array $child
     * @param array                                $options
     *
     * @return \Knp\Menu\ItemInterface|string
     */
    public function addChild($child, array $options = [])
    {
        if (is_array($child) && empty($options)) {
            list($child, $options) = $child;
        }

        return parent::addChild($child, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function addDivider($type = '')
    {
        $name = uniqid();

        if (!empty($type)) {
            $type = '-'.$type;
        }

        return $this->addChild($name, ['extras' => ['divider' => $type]]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDivider()
    {
        return $this->getExtra('divider') !== null;
    }
}
