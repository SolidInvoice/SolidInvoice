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

namespace SolidInvoice\QuoteBundle\Tests\Form\Type;

use Brick\Math\BigDecimal;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\QuoteBundle\DTO\QuoteFormDTO;
use SolidInvoice\QuoteBundle\Enum\QuoteClientMode;
use SolidInvoice\QuoteBundle\Form\Type\ItemType;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Zenstruck\Foundry\Test\Factories;

class QuoteTypeTest extends FormTestCase
{
    use Factories;

    public function testSubmit(): void
    {
        $notes = $this->faker->text;
        $terms = $this->faker->text;
        $discountValue = $this->faker->numberBetween(0, 100);
        $client = ClientFactory::createOne()->_real();

        $formData = [
            'clientMode' => 'existing',
            'client' => $client->getId()->toString(),
            'discount' => [
                'value' => $discountValue,
                'type' => Discount::TYPE_PERCENTAGE,
            ],
            'lines' => [],
            'quoteId' => '10',
            'notes' => $notes,
            'terms' => $terms,
            'total' => '0',
            'baseTotal' => '0',
            'tax' => '0',
            'users' => [],
        ];

        $dto = new QuoteFormDTO();
        $dto->clientMode = QuoteClientMode::Existing;
        $dto->client = $client;
        $dto->quoteId = '10';
        $dto->terms = $terms;
        $dto->notes = $notes;
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(BigDecimal::of($discountValue)->multipliedBy(100));
        $dto->discount = $discount;
        $dto->total = '0';
        $dto->baseTotal = '0';
        $dto->tax = '0';

        $this->assertFormData($this->factory->create(QuoteType::class, new QuoteFormDTO()), $formData, $dto);
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

        $type = new QuoteType($systemConfig, new BillingIdGenerator(new ServiceLocator(['random_number' => static fn () => new class() {
            public function generate(): string
            {
                return '10';
            }
        }]), $systemConfig));
        $itemType = new ItemType($this->registry);

        return [
            new PreloadedExtension([$type, $itemType, new DiscountType($systemConfig)], [
                ChoiceType::class => [
                    new AutocompleteChoiceTypeExtension(new ChecksumCalculator($_SERVER['SOLIDINVOICE_APP_SECRET'])),
                ],
            ]),
        ];
    }
}
