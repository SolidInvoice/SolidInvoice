<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MenuBundle\Builder;

interface BuilderInterface
{
    /**
     * This method is called before the menu is build to validate that the menu should be displayed.
     * From here you can check for specific permissions etc.
     * If this method returns false, then the menu function isn't called.
     */
    public function validate();
}
