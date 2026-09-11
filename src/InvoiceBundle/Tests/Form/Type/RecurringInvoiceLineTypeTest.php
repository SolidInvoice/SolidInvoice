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
use Money\Currency;
use Override;
use SolidInvoice\CoreBundle\Enum\UnitCode;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceLineType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

/**
 * The recurring line form is its own type rather than a reuse of {@see ItemTypeTest}'s, so
 * a field added to one and forgotten on the other would otherwise go unnoticed until a
 * recurring invoice was generated.
 */
final class RecurringInvoiceLineTypeTest extends FormTestCase
{
    /**
     * The field has no blank option, so a payload that omits it — the API, a client that
     * predates the field — has to mean the default rather than an error.
     */
    public function testSubmit(): void
    {
        $formData = [
            'name' => 'Retainer',
            'price' => 125,
            'qty' => '1',
        ];

        $object = new RecurringInvoiceLine();
        $object->setName('Retainer');
        $object->setQty('1');
        $object->setPrice(BigDecimal::of(12500));

        self::assertSame(UnitCode::UNIT, $object->getUnitCode());

        $this->assertFormData(
            $this->factory->create(RecurringInvoiceLineType::class, null, ['currency' => new Currency('USD')]),
            $formData,
            $object
        );
    }

    public function testSubmitUnitOfMeasure(): void
    {
        $formData = [
            'name' => 'Retainer',
            'price' => 125,
            'qty' => '12',
            'unitCode' => UnitCode::HOUR->value,
        ];

        $object = new RecurringInvoiceLine();
        $object->setName('Retainer');
        $object->setQty('12');
        $object->setPrice(BigDecimal::of(12500));
        $object->setUnitCode(UnitCode::HOUR);

        $this->assertFormData(
            $this->factory->create(RecurringInvoiceLineType::class, null, ['currency' => new Currency('USD')]),
            $formData,
            $object
        );
    }

    /**
     * @return array<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new RecurringInvoiceLineType($this->registry)], []),
        ];
    }
}
