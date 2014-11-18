<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Tests\Twig\Extension;

use CSBill\ClientBundle\Twig\Extension\ContactTypesExtension;

class ContactTypesExtensionTest extends \PHPUnit_FrameWork_TestCase
{
    public function testGetGlobals()
    {
        $array = array(
            1,
            3,
            5,
            7,
            9,
        );

        $extension = new ContactTypesExtension($array);

        $this->assertSame(
            array(
                'contact_types' => $array,
            ),
            $extension->getGlobals()
        );
    }

    public function testGetName()
    {
        $extension = new ContactTypesExtension(array());

        $this->assertSame('client_contact_types_extension', $extension->getName());
    }
}
