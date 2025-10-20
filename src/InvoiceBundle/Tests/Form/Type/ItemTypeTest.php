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
use SolidInvoice\CoreBundle\Form\TypeExtension\UnsanitizeSingleQuotesTypeExtension;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\HtmlSanitizer\Type\TextTypeHtmlSanitizerExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ItemTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $description = $this->faker->text;
        $price = $this->faker->randomNumber(3);
        $qty = $this->faker->randomFloat(2);

        $formData = [
            'description' => $description,
            'price' => $price,
            'qty' => $qty,
        ];

        $currency = new Currency('USD');

        $object = new Line();
        $object->setDescription($description);
        $object->setQty($qty);
        $object->setPrice(BigDecimal::of($price * 100));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => $currency]), $formData, $object);
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$itemType], []),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new TextTypeHtmlSanitizerExtension(new ServiceLocator(['default' => fn () => new HtmlSanitizer(new HtmlSanitizerConfig())])),
            new UnsanitizeSingleQuotesTypeExtension(),
            ...parent::getTypeExtensions(),
        ];
    }
}
