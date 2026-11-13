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

namespace SolidInvoice\ProductBundle\Tests\Entity;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CoreBundle\Billing\LineName;
use SolidInvoice\ProductBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function str_repeat;

#[CoversClass(Product::class)]
final class ProductTest extends KernelTestCase
{
    public function testQuantityDefaultsToOne(): void
    {
        self::assertTrue(new Product()->getQuantity()->isEqualTo(1));
    }

    #[DataProvider('validationProvider')]
    public function testValidation(callable $configure, bool $valid): void
    {
        $product = new Product();
        $product->setName('Website design');
        $configure($product);

        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($product);

        self::assertCount($valid ? 0 : 1, $violations, (string) $violations);
    }

    /**
     * @return iterable<string, array{callable(Product): mixed, bool}>
     */
    public static function validationProvider(): iterable
    {
        yield 'valid' => [static fn (Product $p): Product => $p->setPrice(BigDecimal::of('10')), true];
        yield 'blank name' => [static fn (Product $p): Product => $p->setName(''), false];
        yield 'name at the limit' => [static fn (Product $p): Product => $p->setName(str_repeat('a', LineName::MAX_LENGTH)), true];
        yield 'name too long' => [static fn (Product $p): Product => $p->setName(str_repeat('a', LineName::MAX_LENGTH + 1)), false];
        yield 'negative price' => [static fn (Product $p): Product => $p->setPrice(BigDecimal::of('-1')), false];
        yield 'zero quantity' => [static fn (Product $p): Product => $p->setQuantity(BigDecimal::zero()), false];
        yield 'price beyond two decimals' => [static fn (Product $p): Product => $p->setPrice(BigDecimal::of('4500.505')), true];
    }
}
