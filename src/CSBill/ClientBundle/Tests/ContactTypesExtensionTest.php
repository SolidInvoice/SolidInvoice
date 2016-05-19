<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
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
        $manager = \Mockery::mock('Doctrine\Common\Persistence\ObjectManager');
        $registry = \Mockery::mock('Doctrine\Bundle\DoctrineBundle\Registry', ['getManager' => $manager]);

        $extension = new ContactTypesExtension($registry);

        $functions = $extension->getFunctions();

        $this->assertTrue(is_array($functions));
        $this->assertCount(1, $functions);

        $this->assertInstanceof('Twig_SimpleFunction', $functions[0]);
    }

    public function testGetContactTypes()
    {
        $array = [
            1,
            3,
            5,
            7,
            9,
        ];

        $manager = \Mockery::mock('Doctrine\Common\Persistence\ObjectManager');
        $objectRepository = \Mockery::mock('Doctrine\Common\Persistence\ObjectRepository', ['findAll' => $array]);
        $registry = \Mockery::mock('Doctrine\Bundle\DoctrineBundle\Registry', ['getManager' => $manager]);

        $manager->shouldReceive('getRepository')
            ->once()
            ->with('CSBillClientBundle:ContactType')
            ->andReturn($objectRepository);

        $extension = new ContactTypesExtension($registry);

        $this->assertSame($array, $extension->getContactTypes());

        // Run twice, to ensure the contact types is cached and no duplicate queries are executed
        // when getting the contact types more than once
        $this->assertSame($array, $extension->getContactTypes());
    }

    public function testGetName()
    {
        $manager = \Mockery::mock('Doctrine\Common\Persistence\ObjectManager');
        $registry = \Mockery::mock('Doctrine\Bundle\DoctrineBundle\Registry', ['getManager' => $manager]);

        $extension = new ContactTypesExtension($registry);

        $this->assertSame('client_contact_types_extension', $extension->getName());
    }
}
