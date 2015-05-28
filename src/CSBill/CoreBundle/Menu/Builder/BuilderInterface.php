<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\CoreBundle\Menu\Builder;

interface BuilderInterface
{
    /**
     * This method is called before the menu is build to validate that the menu should be displayed.
     * From here you can check for specific permissions etc.
     * If this method returns false, then the menu function isn't called.
     */
    public function validate();
}
