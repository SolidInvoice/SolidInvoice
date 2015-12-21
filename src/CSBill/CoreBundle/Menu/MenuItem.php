<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\MenuItem as BaseItem;

class MenuItem extends BaseItem implements ItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function addDivider($type = '')
    {
        $name = uniqid();

        if (!empty($type)) {
            $type = '-'.$type;
        }

        return $this->addChild($name, array('extras' => array('divider' => $type)));
    }

    /**
     * {@inheritdoc}
     */
    public function isDivider()
    {
        return $this->getExtra('divider') !== null;
    }
}
