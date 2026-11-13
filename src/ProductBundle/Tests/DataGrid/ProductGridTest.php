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

namespace SolidInvoice\ProductBundle\Tests\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\ProductBundle\DataGrid\ProductGrid;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductGridTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * @return iterable<string, array{bool}>
     */
    public static function gates(): iterable
    {
        yield 'feature on' => [true];
        yield 'feature off' => [false];
    }

    #[DataProvider('gates')]
    public function testBatchDeleteOnlyRunsWhenTheFeatureIsOn(bool $enabled): void
    {
        $product = ProductFactory::createOne(['company' => $this->company]);

        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn($enabled);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $action = [...new ProductGrid($gate)->batchActions()][0]->getAction();

        self::assertNotNull($action);
        $action($em->getRepository(Product::class), [$product->getId()->toString()]);

        $em->clear();

        self::assertSame($enabled ? 0 : 1, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM products'));
    }
}
