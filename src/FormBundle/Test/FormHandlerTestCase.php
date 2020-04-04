<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Form\Extension\FormHelpExtension;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType as InvoiceItemType;
use SolidInvoice\MoneyBundle\Form\Extension\MoneyExtension;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\QuoteBundle\Form\Type\ItemType as QuoteItemType;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidWorx\FormHandler\Test\FormHandlerTestCase as BaseTestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class FormHandlerTestCase extends BaseTestCase
{
    use DoctrineTestTrait;
    use
        MockeryPHPUnitIntegration;

    /**
     * @var Generator
     */
    protected $faker;

    protected function setUp()
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    protected function getExtensions(): array
    {
        $currency = new Currency('USD');

        return [
            new PreloadedExtension(
                [
                    new HiddenMoneyType($currency),
                    new CurrencyType('en_US'),
                    new ContactDetailType($this->registry->getRepository(ContactType::class)),
                    new InvoiceType($currency),
                    new QuoteType($currency),
                    new InvoiceItemType($this->registry),
                    new QuoteItemType($this->registry),
                    new DiscountType($currency),
                ],
                []
            ),
            new DoctrineOrmExtension($this->registry),
        ];
    }

    protected function getTypeExtensions(): array
    {
        $validator = M::mock(ValidatorInterface::class);

        $validator->shouldReceive('validate')->zeroOrMoreTimes()->andReturn([]);

        return [
            new FormHelpExtension(),
            new MoneyExtension(new Currency('USD')),
            new FormTypeValidatorExtension($validator),
        ];
    }
}
