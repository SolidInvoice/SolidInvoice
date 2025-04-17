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
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mockery as M;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

class RecurringInvoiceTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $client = (new Client())->setCompany($this->company)->setCurrencyCode('USD');

        $this->registry->getManager()->persist($client);

        $notes = $this->faker->text;
        $terms = $this->faker->text;
        $discountValue = $this->faker->numberBetween(0, 100);
        $formData = [
            'client' => [
                'autocomplete' => (
                    $this->em->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform ?
                    $client->getId()->toRfc4122() :
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
            'date_start' => $this->faker->dateTime,
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
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        $invoiceType = new RecurringInvoiceType($systemConfig, $this->registry);
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([
                $invoiceType,
                $itemType,
                new DiscountType($systemConfig),
            ], []),
        ];
    }
}
