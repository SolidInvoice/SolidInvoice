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

namespace SolidInvoice\FormBundle\Test;

use Faker\Factory;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Form\Extension\FormHelpExtension;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Form\Type\UuidEntityType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType as InvoiceItemType;
use SolidInvoice\MoneyBundle\Form\Extension\MoneyExtension;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\QuoteBundle\Form\Type\ItemType as QuoteItemType;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\FormHandler\Test\FormHandlerTestCase as BaseTestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

abstract class FormHandlerTestCase extends BaseTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        return [
            new PreloadedExtension(
                [
                    new HiddenMoneyType(),
                    new CurrencyType('en_US'),
                    new ContactDetailType(),
                    new InvoiceType($systemConfig, $this->registry, new BillingIdGenerator(new ServiceLocator([]), $systemConfig)),
                    new QuoteType($systemConfig, $this->registry, new BillingIdGenerator(new ServiceLocator([]), $systemConfig)),
                    new InvoiceItemType($this->registry),
                    new QuoteItemType($this->registry),
                    new DiscountType($systemConfig),
                    new BaseEntityAutocompleteType($this->createMock(UrlGeneratorInterface::class)),
                    new UuidEntityType($this->registry),
                ],
                [
                    [
                        new AutocompleteChoiceTypeExtension(
                            new ChecksumCalculator('abc')
                        ),
                    ]
                ]
            ),
            new DoctrineOrmExtension($this->registry),

        ];
    }

    /**
     * @return array<FormTypeExtensionInterface>
     */
    protected function getTypeExtensions(): array
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
            new AutocompleteChoiceTypeExtension(
                new ChecksumCalculator('abc')
            ),
        ];
    }
}
