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
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery as M;
use Money\Currency;
use Override;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Enum\InvoiceClientMode;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Zenstruck\Foundry\Test\Factories;

final class InvoiceTypeTest extends FormTestCase
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
            'invoiceId' => '10',
            'notes' => $notes,
            'terms' => $terms,
            'total' => '0',
            'baseTotal' => '0',
            'invoiceDate' => '2021-01-01',
            'tax' => '0',
            'users' => [],
        ];

        $dto = new InvoiceFormDTO();
        $dto->clientMode = InvoiceClientMode::Existing;
        $dto->client = $client;
        $dto->invoiceId = '10';
        $dto->terms = $terms;
        $dto->notes = $notes;

        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(BigDecimal::of($discountValue)->multipliedBy(100));

        $dto->discount = $discount;
        $dto->total = '0';
        $dto->baseTotal = '0';
        $dto->tax = '0';
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');

        $this->assertFormData($this->factory->create(InvoiceType::class, new InvoiceFormDTO()), $formData, $dto);
    }

    public function testSubmitWithNewClient(): void
    {
        $notes = $this->faker->text;
        $terms = $this->faker->text;
        $discountValue = $this->faker->numberBetween(0, 100);

        $formData = [
            'clientMode' => 'new',
            'newClientName' => 'New Client',
            'newContactFirstName' => 'John',
            'newContactLastName' => 'Doe',
            'newContactEmail' => 'john@example.com',
            'discount' => [
                'value' => $discountValue,
                'type' => Discount::TYPE_PERCENTAGE,
            ],
            'lines' => [],
            'invoiceId' => '10',
            'notes' => $notes,
            'terms' => $terms,
            'total' => '0',
            'baseTotal' => '0',
            'invoiceDate' => '2021-01-01',
            'tax' => '0',
        ];

        $dto = new InvoiceFormDTO();
        $dto->clientMode = InvoiceClientMode::NewClient;
        $dto->newClientName = 'New Client';
        $dto->newContactFirstName = 'John';
        $dto->newContactLastName = 'Doe';
        $dto->newContactEmail = 'john@example.com';
        $dto->invoiceId = '10';
        $dto->terms = $terms;
        $dto->notes = $notes;

        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(BigDecimal::of($discountValue)->multipliedBy(100));

        $dto->discount = $discount;
        $dto->total = '0';
        $dto->baseTotal = '0';
        $dto->tax = '0';
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');

        $this->assertFormData($this->factory->create(InvoiceType::class, new InvoiceFormDTO()), $formData, $dto);
    }

    /**
     * @return array<FormExtensionInterface>
     */
    #[Override]
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

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        $invoiceType = new InvoiceType($systemConfig, new BillingIdGenerator(new ServiceLocator(['random_number' => static fn () => new class() {
            public function generate(): string
            {
                return '10';
            }
        }]), $systemConfig), $featureGate);
        $itemType = new ItemType($this->registry);

        $customFieldsType = new CustomFieldValueCollectionType(
            M::mock(CustomFieldRepository::class, ['findByTargetOrdered' => []]),
            M::mock(CustomFieldValueRepository::class, ['findForRecord' => []]),
            new CustomFieldTypeResolver(),
            $this->createStub(EntityManagerInterface::class),
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([
                $invoiceType,
                $itemType,
                new DiscountType($systemConfig),
                $customFieldsType,
                new BaseEntityAutocompleteType($this->createStub(UrlGeneratorInterface::class))
            ], [
                ChoiceType::class => [
                    new AutocompleteChoiceTypeExtension(new ChecksumCalculator($_SERVER['SOLIDINVOICE_APP_SECRET'])),
                ],
            ]),
        ];
    }
}
