<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Menu\Builder;

interface BuilderInterface
{
	/**
	 * This method is called before the menu is build to validate that the menu should be displayed. From here you can check for specific permissions etc. If this method returns false, then the menu function isn't called
	 */
	public function validate();
}
