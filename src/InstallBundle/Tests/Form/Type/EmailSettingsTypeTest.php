<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InstallBundle\Form\Type\EmailSettingsType;

class EmailSettingsTypeTest extends FormTestCase
{
    private $transports = [
	'mail' => 'PHP Mail',
	'sendmail' => 'Sendmail',
	'smtp' => 'SMTP',
	'gmail' => 'Gmail',
    ];

    public function testSubmit()
    {
	foreach ($this->getFormData() as $data) {
	    $form = $this->factory->create(EmailSettingsType::class, null, ['transports' => $this->transports]);
	    $this->assertFormData($form, $data[0], $data[1]);
	}
    }

    /**
     * @return array
     */
    private function getFormData()
    {
	yield [
	    [
		'transport' => 'mail',
		'host' => 'localhost',
		'port' => 1234,
		'encryption' => 'ssl',
		'user' => 'root',
		'password' => 'password',
	    ],
	    [
		'transport' => 'mail',
		'host' => null,
		'port' => null,
		'encryption' => null,
		'user' => null,
		'password' => null,
	    ],
	];

	yield [
	    [
		'transport' => 'sendmail',
		'host' => 'localhost',
		'port' => 1234,
		'encryption' => 'ssl',
		'user' => 'root',
		'password' => 'password',
	    ],
	    [
		'transport' => 'sendmail',
		'host' => null,
		'port' => null,
		'encryption' => null,
		'user' => null,
		'password' => null,
	    ],
	];

	yield [
	    [
		'transport' => 'smtp',
		'host' => 'localhost',
		'port' => 1234,
		'encryption' => 'ssl',
		'user' => 'root',
		'password' => 'password',
	    ],
	    [
		'transport' => 'smtp',
		'host' => 'localhost',
		'port' => 1234,
		'encryption' => 'ssl',
		'user' => 'root',
		'password' => 'password',
	    ],
	];

	yield [
	   [
	       'transport' => 'gmail',
	       'host' => 'localhost',
	       'port' => 1234,
	       'encryption' => 'ssl',
	       'user' => 'root',
	       'password' => 'password',
	   ],
	   [
	       'transport' => 'gmail',
	       'host' => null,
	       'port' => null,
	       'encryption' => null,
	       'user' => 'root',
	       'password' => 'password',
	   ],
       ];
    }
}
