<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\FormBundle\Test;

use CSBill\ClientBundle\Form\Type\ContactDetailType;
use CSBill\CoreBundle\Form\Extension\FormHelpExtension;
use CSBill\CoreBundle\Test\Traits\DoctrineTestTrait;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Form\Type\ItemType as InvoiceItemType;
use CSBill\MoneyBundle\Form\Extension\MoneyExtension;
use CSBill\MoneyBundle\Form\Type\CurrencyType;
use CSBill\MoneyBundle\Form\Type\HiddenMoneyType;
use CSBill\QuoteBundle\Form\Type\ItemType as QuoteItemType;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use Faker\Factory;
use Faker\Generator;
use Mockery as M;
use Money\Currency;
use SolidWorx\FormHandler\Test\FormHandlerTestCase as BaseTestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class FormHandlerTestCase extends BaseTestCase
{
    use DoctrineTestTrait;

    /**
     * @var Generator
     */
    protected $faker;

    protected function setUp()
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->setupDoctrine();
    }

    abstract protected function getEntityNamespaces(): array;

    abstract protected function getEntities(): array;

    protected function getExtensions(): array
    {
        $currency = new Currency('USD');

        return [
            new PreloadedExtension(
                [
                    new HiddenMoneyType($currency),
                    new CurrencyType('en_US'),
                    new ContactDetailType([]),
                    new InvoiceType($currency),
                    new QuoteType($currency),
                    new InvoiceItemType($this->registry),
                    new QuoteItemType($this->registry),
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
