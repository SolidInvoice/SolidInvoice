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
use Brick\Math\Exception\MathException;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Mockery as M;
use Money\Currency;
use Override;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceLineType;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

final class RecurringInvoiceTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $client = new Client()->setCompany($this->company)->setCurrencyCode('USD');

        $this->registry->getManager()->persist($client);

        $notes = $this->faker->text();
        $terms = $this->faker->text();
        $discountValue = $this->faker->numberBetween(0, 100);
        $formData = [
            'client' => [
                'autocomplete' => (
                    $this->em->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform ?
                    $client->getId()->toString() :
                    $client->getId()->toString()
                ),
            ],
            'discount' => [
                'value' => $discountValue,
                'type' => Discount::TYPE_PERCENTAGE,
            ],
            'lines' => [],
            'notes' => $notes,
            'terms' => $terms,
            'total' => 0,
            'baseTotal' => 0,
            'tax' => 0,
            'date_start' => $this->faker->dateTime(),
        ];

        $object = new RecurringInvoice();
        $object->setRecurringOptions(new RecurringOptions());
        $object->setClient($client);

        $data = clone $object;

        $object->setTerms($terms);
        $object->setNotes($notes);

        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(BigDecimal::of($discountValue)->multipliedBy(100));

        $object->setDiscount($discount);

        $this->assertFormData($this->factory->create(RecurringInvoiceType::class, $data), $formData, $object);
    }

    /**
     * A recurring invoice is saved by submitting its form, not through a form manager, so the
     * dragged order has to survive that submit on its own.
     *
     * The rows come back from the drag renumbered `0..n-1`, so the third line's values arrive
     * as row 0. What moves is the values, not the objects — line 0 keeps its id and picks up
     * what used to be on line 2 — but the order the reader gets is the order they dragged,
     * and the positions stay contiguous.
     *
     * @throws MathException
     */
    public function testSubmittingLinesInADraggedOrderKeepsThatOrder(): void
    {
        $client = new Client()->setCompany($this->company)->setCurrencyCode('USD');
        $this->registry->getManager()->persist($client);

        $invoice = new RecurringInvoice();
        $invoice->setRecurringOptions(new RecurringOptions());
        $invoice->setClient($client);

        foreach (['First', 'Second', 'Third'] as $description) {
            $invoice->addLine(new RecurringInvoiceLine()->setDescription($description)->setPrice(10000)->setQty(1));
        }

        $form = $this->factory->create(RecurringInvoiceType::class, $invoice);

        $form->submit([
            'client' => ['autocomplete' => $client->getId()->toString()],
            'lines' => [
                ['description' => 'Third', 'price' => '100.00', 'qty' => '1'],
                ['description' => 'First', 'price' => '100.00', 'qty' => '1'],
                ['description' => 'Second', 'price' => '100.00', 'qty' => '1'],
            ],
            'total' => 0,
            'baseTotal' => 0,
            'tax' => 0,
            'date_start' => $this->faker->dateTime()->format('Y-m-d'),
        ]);

        self::assertSame(
            ['Third', 'First', 'Second'],
            $invoice->getLines()->map(static fn (RecurringInvoiceLine $line): ?string => $line->getDescription())->toArray(),
        );

        self::assertSame(
            [0, 1, 2],
            $invoice->getLines()->map(static fn (RecurringInvoiceLine $line): int => $line->getPosition())->toArray(),
        );
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

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        $invoiceType = new RecurringInvoiceType($systemConfig, $this->registry, $featureGate);
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
                new RecurringInvoiceLineType($this->registry),
                new DiscountType($systemConfig),
                $customFieldsType,
            ], []),
        ];
    }
}
