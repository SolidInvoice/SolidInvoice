<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Tests;

use CSBill\CoreBundle\Form\Extension;
use CSBill\CoreBundle\Form\Type;
use CSBill\CoreBundle\Security\Encryption;
use CSBill\MoneyBundle\Form\Extension\MoneyExtension;
use CSBill\MoneyBundle\Form\Type\HiddenMoneyType;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ORM\Tools\SchemaTool;
use Faker\Factory;
use Faker\Generator;
use Mockery as M;
use Money\Currency;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormTestCase extends TypeTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    protected $registry;

    protected $em;

    protected function setUp()
    {
        $this->faker = Factory::create();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getInternalExtension())
            ->addTypeExtensions($this->getTypedExtensions())
            ->addTypes($this->getTypes())
            ->getFormFactory();

        $this->dispatcher = M::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    /**
     * Get registered form extensions.
     *
     * @return array
     */
    protected function getTypedExtensions()
    {
        $validator = M::mock(ValidatorInterface::class);

        $validator->shouldReceive('validate')->zeroOrMoreTimes()->andReturn([]);

        return [
            new Extension\FormHelpExtension(),
            new MoneyExtension(new Currency('USD')),
            new FormTypeValidatorExtension($validator),
        ];
    }

    protected function createRegistryMock($name, $em)
    {
        $this->registry = M::mock(ManagerRegistry::class);
        $this->registry->shouldReceive('getManager')
            ->with($name)
            ->andReturn($em);

        $this->registry->shouldReceive('getManagers')
            ->with()
            ->andReturn(['default' => $em]);

        $this->registry->shouldReceive('getManagerForClass')
            ->andReturn($em);

        return $this->registry;
    }

    protected function createSchema($em)
    {
        $schemaTool = new SchemaTool($em);
        $classes = [];

        foreach ($this->getEntities() as $entityClass) {
            $classes[] = $em->getClassMetadata($entityClass);
        }

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    /**
     * Get registered form types.
     *
     * @return array
     */
    protected function getTypes()
    {
        return [
            'select2' => new Type\Select2Type(),
            'image_upload' => new Type\ImageUploadType(
                \Mockery::mock('Symfony\Component\HttpFoundation\Session\SessionInterface'),
                new Encryption(rand())
            ),
        ];
    }

    protected function getEntityNamespaces()
    {
        return [];
    }

    protected function getEntities()
    {
        return [];
    }

    protected function assertFormData($form, array $formData, $object)
    {
        $this->assertNotEmpty($formData);

        if (!$form instanceof FormInterface) {
            $form = $this->factory->create($form);
        }

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    private function getInternalExtension()
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();

        $this->em->getConfiguration()->setEntityNamespaces($this->getEntityNamespaces());

        $this->createRegistryMock('default', $this->em);
        $this->createSchema($this->em);

        if (!DoctrineType::hasType('uuid')) {
            DoctrineType::addType('uuid', UuidType::class);
        }

        $type = new EntityType($this->registry);
        $moneyType = new HiddenMoneyType(new Currency('USD'));

        $extensions = array_merge([
            new PreloadedExtension([$type, $moneyType], []),
            new DoctrineOrmExtension($this->registry),
        ], $this->getExtensions());

        return $extensions;
    }
}
