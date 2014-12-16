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

class ContactTypesExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFunctions()
    {
        $registry = $this->getMock('Doctrine\Bundle\DoctrineBundle\Registry', array('getManager'), array(), '', false);
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($manager));

        $extension = new ContactTypesExtension($registry);

        $functions = $extension->getFunctions();

        $this->assertTrue(is_array($functions));
        $this->assertCount(1, $functions);

        $this->assertInstanceof('Twig_SimpleFunction', $functions[0]);
    }

    public function testGetContactTypes()
    {
        $array = array(
            1,
            3,
            5,
            7,
            9,
        );

        $registry = $this->getMock('Doctrine\Bundle\DoctrineBundle\Registry', array('getManager'), array(), '', false);
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($manager));

        $manager->expects($this->once())
            ->method('getRepository')
            ->with('CSBillClientBundle:ContactType')
            ->will($this->returnValue($objectRepository));

        $objectRepository->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($array));

        $extension = new ContactTypesExtension($registry);

        $this->assertSame($array, $extension->getContactTypes());
    }

    public function testGetName()
    {
        $registry = $this->getMock('Doctrine\Bundle\DoctrineBundle\Registry', array('getManager'), array(), '', false);
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($manager));

        $extension = new ContactTypesExtension($registry);

        $this->assertSame('client_contact_types_extension', $extension->getName());
    }
}
