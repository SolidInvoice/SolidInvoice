<?php

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\MenuItem as BaseItem;

class MenuItem extends BaseItem
{
    public function addDivider($type = '')
    {
        $name = uniqid();

        if (!empty($type)) {
            $type = '-'.$type;
        }

        $child = $this->addChild($name, array('extras' => array('divider' => $type)));

        return $child;
    }

    public function isDivider()
    {
        return $this->getExtra('divider') !== null;
    }
}
