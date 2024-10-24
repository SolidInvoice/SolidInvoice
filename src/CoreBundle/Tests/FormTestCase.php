<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Tests;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Faker\Factory;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use Ramsey\Uuid\Doctrine\UuidType;
use SolidInvoice\CoreBundle\Form\Extension\FormHelpExtension;
use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\MoneyBundle\Form\Extension\MoneyExtension;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class FormTestCase extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    protected Generator $faker;

    protected FormBuilder $builder;

    protected EventDispatcherInterface $dispatcher;

    protected FormFactoryInterface $factory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getInternalExtension())
            ->addTypeExtensions($this->getTypedExtensions())
            ->addTypes($this->getTypes())
            ->addTypeGuessers($this->getTypeGuessers())
            ->getFormFactory();

        $this->dispatcher = M::mock(EventDispatcherInterface::class);
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    /**
     * Get registered form extensions.
     *
     * @return array<FormTypeExtensionInterface>
     */
    protected function getTypedExtensions(): array
    {
        $validator = M::mock(ValidatorInterface::class);

        $validator
            ->shouldReceive('validate')
            ->zeroOrMoreTimes()
            ->andReturn(new ConstraintViolationList());

        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        return [
            new FormHelpExtension(),
            new MoneyExtension($systemConfig),
            new FormTypeValidatorExtension($validator),
        ];
    }

    /**
     * Get registered form types.
     *
     * @return array<string, FormTypeInterface>
     */
    protected function getTypes(): array
    {
        return [
            'select2' => new Select2Type(),
            'image_upload' => new ImageUploadType(),
        ];
    }

    /**
     * @param string|FormInterface $form
     * @param array<string, mixed> $formData
     */
    protected function assertFormData($form, array $formData, mixed $object): void
    {
        self::assertNotEmpty($formData);

        if (! $form instanceof FormInterface) {
            $form = $this->factory->create($form, $object);
        }

        // submit the data to the form directly
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array<FormExtensionInterface>
     * @throws Exception
     */
    private function getInternalExtension(): array
    {
        if (! DoctrineType::hasType('uuid')) {
            DoctrineType::addType('uuid', UuidType::class);
        }

        $type = new EntityType($this->registry);
        $moneyType = new HiddenMoneyType();

        return array_merge([
            new PreloadedExtension([$type, $moneyType], []),
            new DoctrineOrmExtension($this->registry),
        ], $this->getExtensions());
    }

    abstract public function testSubmit(): void;

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [];
    }

    /**
     * @return array<FormTypeExtensionInterface>
     */
    protected function getTypeExtensions(): array
    {
        return [];
    }

    /**
     * @return array<FormTypeGuesserInterface>
     */
    protected function getTypeGuessers(): array
    {
        return [];
    }
}
