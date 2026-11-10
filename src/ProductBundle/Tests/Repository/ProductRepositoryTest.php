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

namespace SolidInvoice\ProductBundle\Tests\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_map;

#[CoversClass(ProductRepository::class)]
final class ProductRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Product::class);
    }

    public function testDeleteProductsOnlyDeletesCurrentCompany(): void
    {
        $mine = ProductFactory::createOne(['company' => $this->company]);
        $otherCompany = CompanyFactory::createOne();
        $theirs = ProductFactory::createOne(['company' => $otherCompany]);

        // Creating a company can move the active one, so pin it to the first.
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $this->repository->deleteProducts([$mine->getId(), $theirs->getId()]);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        // Read raw: the company SQL filter would hide the other company's row from the ORM.
        // Through the ulid type, since the column is binary on most platforms but a native uuid on PostgreSQL.
        $connection = $em->getConnection();
        $ulid = Type::getType(UlidType::NAME);

        self::assertSame(
            [$theirs->getId()->toBase58()],
            array_map(static fn (string $id): string => $ulid->convertToPHPValue($id, $connection->getDatabasePlatform())->toBase58(), $connection->fetchFirstColumn('SELECT id FROM products')),
        );
    }

    public function testSearchMatchesNameAndDescription(): void
    {
        ProductFactory::createOne(['company' => $this->company, 'name' => 'Website design']);
        ProductFactory::createOne(['company' => $this->company, 'name' => 'SEO', 'description' => 'web audit']);
        ProductFactory::createOne(['company' => $this->company, 'name' => 'Hosting']);

        self::assertSame(['SEO', 'Website design'], array_map(static fn (Product $p): string => $p->getName(), $this->repository->search('web')));
        self::assertSame([], $this->repository->search('zzz'));
    }
}
