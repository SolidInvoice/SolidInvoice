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

namespace SolidInvoice\InvoiceBundle\Tests\Form\Type;

use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Form\Type\UuidEntityType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Zenstruck\Foundry\Test\Factories;

class InvoiceTypeTest extends FormTestCase
{
    use Factories;

    public function testSubmit(): void
    {
        $notes = $this->faker->text;
        $terms = $this->faker->text;
        $discountValue = $this->faker->numberBetween(0, 100);
        $client = ClientFactory::createOne()->_real();
        $formData = [
            'client' => $client->getId()->toString(),
            'discount' => [
                'value' => $discountValue,
                'type' => Discount::TYPE_PERCENTAGE,
            ],
            'lines' => [],
            'invoiceId' => '10',
            'notes' => $notes,
            'terms' => $terms,
            'total' => 0,
            'baseTotal' => 0,
            'invoiceDate' => new DateTimeImmutable(),
            'tax' => 0,
        ];

        $object = new Invoice();
        $object->setClient($client);
        $object->setInvoiceId('10');
        $data = clone $object;
        $data->setUuid($object->getUuid());

        $object->setTerms($terms);
        $object->setNotes($notes);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(BigDecimal::of($discountValue)->multipliedBy(100));
        $object->setDiscount($discount);
        $object->setTotal(BigDecimal::zero());
        $object->setBaseTotal(BigDecimal::zero());
        $object->setTax(BigDecimal::zero());

        $this->assertFormData($this->factory->create(InvoiceType::class, $data), $formData, $object);
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

        $systemConfig
            ->shouldReceive('get')
            ->zeroOrMoreTimes()
            ->andReturn('random_number');

        $invoiceType = new InvoiceType($systemConfig, $this->registry, new BillingIdGenerator(new ServiceLocator(['random_number' => static fn () => new class() {
            public function generate(): string
            {
                return '10';
            }
        }]), $systemConfig));
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([
                $invoiceType,
                $itemType,
                new DiscountType($systemConfig),
                new BaseEntityAutocompleteType($this->createMock(UrlGeneratorInterface::class)),
                new UuidEntityType($this->registry),
            ], [
                ChoiceType::class => [
                    new AutocompleteChoiceTypeExtension(new ChecksumCalculator('abc')),
                ],
            ]),
        ];
    }
}
