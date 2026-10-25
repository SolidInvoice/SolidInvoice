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
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

final class ItemTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $name = $this->faker->sentence(3);
        $description = $this->faker->text();
        $price = $this->faker->randomNumber(3);
        $qty = '12.345678';

        $formData = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'qty' => $qty,
        ];

        $currency = new Currency('USD');

        $object = new Line();
        $object->setName($name);
        $object->setDescription($description);
        $object->setQty($qty);
        $object->setPrice(BigDecimal::of($price * 100));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => $currency]), $formData, $object);
    }

    public function testSubmitPreservesSpecialCharacters(): void
    {
        $name = 'Item + discount & "special" chars: 100% off';
        $price = 100;
        $qty = '1';

        $formData = [
            'name' => $name,
            'price' => $price,
            'qty' => $qty,
        ];

        $currency = new Currency('USD');

        $object = new Line();
        $object->setName($name);
        $object->setQty($qty);
        $object->setPrice(BigDecimal::of($price * 100));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => $currency]), $formData, $object);
    }

    public function testSubmitUnitOfMeasure(): void
    {
        $formData = [
            'description' => 'Consulting',
            'price' => 125,
            'qty' => '12',
            'unitCode' => UnitCode::HOUR->value,
        ];

        $object = new Line();
        $object->setDescription('Consulting');
        $object->setQty('12');
        $object->setPrice(BigDecimal::of(12500));
        $object->setUnitCode(UnitCode::HOUR);

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => new Currency('USD')]), $formData, $object);
    }

    /**
     * The name field comes first so that a line submitted with only a description still
     * derives one. Nothing in the UI submits this shape today, but the form has to survive
     * a stale browser tab that does.
     */
    public function testSubmitWithOnlyADescriptionDerivesTheName(): void
    {
        $formData = [
            'name' => '',
            'description' => "Website design\nIncluding two rounds of revisions.",
            'price' => 100,
            'qty' => '1',
        ];

        $object = new Line();
        $object->setDescription("Website design\nIncluding two rounds of revisions.");
        $object->setQty('1');
        $object->setPrice(BigDecimal::of(10000));

        self::assertSame('Website design', $object->getName());

        $this->assertFormData(
            $this->factory->create(ItemType::class, null, ['currency' => new Currency('USD')]),
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
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$itemType], []),
        ];
    }
}
