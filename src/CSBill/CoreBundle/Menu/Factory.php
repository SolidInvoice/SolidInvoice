<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\Silex\RouterAwareFactory;
use CSBill\CoreBundle\Menu\MenuItem;

class Factory extends RouterAwareFactory
{
    public function createItem($name, array $options = array())
    {
        $item = new MenuItem($name, $this);

        $options = $this->buildOptions($options);
        $this->configureItem($item, $options);

        return $item;
    }
}
