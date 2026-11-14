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

namespace SolidInvoice\ProductBundle\Tests\Form\Type;

use Brick\Math\BigDecimal;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Form\Type\ProductType;

final class ProductTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $product = new Product();
        $form = $this->factory->create(ProductType::class, $product);

        $form->submit([
            'name' => 'Website design',
            'description' => 'x',
            'price' => '4500.50',
            'quantity' => '2',
            'tax' => null,
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame('Website design', $product->getName());
        self::assertSame('x', $product->getDescription());
        self::assertTrue($product->getPrice()->isEqualTo(BigDecimal::of('4500.50')));
        self::assertTrue($product->getQuantity()->isEqualTo(BigDecimal::of('2')));
        self::assertNull($product->getTax());
    }

    public function testPriceKeepsDecimalPrecision(): void
    {
        $product = new Product();
        $form = $this->factory->create(ProductType::class, $product);

        $form->submit(['name' => 'Precise', 'price' => '0.123456', 'quantity' => '1.5']);

        self::assertTrue($product->getPrice()->isEqualTo(BigDecimal::of('0.123456')));
        self::assertTrue($product->getQuantity()->isEqualTo(BigDecimal::of('1.5')));
    }

    public function testPriceRendersTheStoredValue(): void
    {
        $product = new Product()->setName('Existing')->setPrice(BigDecimal::of('19.99'));
        $view = $this->factory->create(ProductType::class, $product)->createView();

        self::assertSame('19.99', $view['price']->vars['value']);
    }
}
