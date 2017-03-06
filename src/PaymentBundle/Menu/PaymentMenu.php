<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Menu;

class PaymentMenu
{
    /**
     * @return array
     */
    public static function main()
    {
	return [
	    'payment.menu.main',
	    [
		'route' => '_payments_index',
		'extras' => [
		    'icon' => 'credit-card',
		],
	    ],
	];
    }

    /**
     * @return array
     */
    public static function methods()
    {
	return [
	    'payment.menu.methods',
	    [
		'route' => '_payment_settings_index',
		'extras' => [
		    'icon' => 'credit-card',
		],
	    ],
	];
    }
}
