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

namespace SolidInvoice\ProductBundle\Tests\Functional;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

#[Group('functional')]
final class ProductCrudTest extends WebTestCase
{
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;
    use HasBrowser;

    public function testIndexShowsGrid(): void
    {
        ProductFactory::createOne(['name' => 'Listed product', 'price' => BigDecimal::of('1234.5'), 'company' => $this->company]);

        $this->browser()
            ->actingAs(UserFactory::createOne(['companies' => [$this->company]]))
            ->visit('/products/')
            ->assertSuccessful()
            ->assertContains('product_grid')
            ->assertContains('Listed product')
            ->assertContains('1,234.50');
    }

    public function testCreate(): void
    {
        $this->browser()
            ->actingAs(UserFactory::createOne(['companies' => [$this->company]]))
            ->visit('/products/add')
            ->assertSuccessful()
            ->fillField('product[name]', 'Website design')
            ->fillField('product[description]', 'A full site')
            ->fillField('product[price]', '4500.50')
            ->fillField('product[quantity]', '2')
            ->click('Save')
            ->assertOn('/products/')
            ->assertContains('Product successfully saved');

        $product = $this->em->getRepository(Product::class)->findOneBy(['name' => 'Website design']);
        self::assertInstanceOf(Product::class, $product);
        self::assertTrue($product->getPrice()->isEqualTo(BigDecimal::of('4500.50')));
        self::assertTrue($product->getQuantity()->isEqualTo(BigDecimal::of('2')));
        self::assertSame($this->company->getId()->toString(), $product->getCompany()->getId()->toString());
    }

    public function testEdit(): void
    {
        $product = ProductFactory::createOne(['name' => 'Old name', 'company' => $this->company]);

        $this->browser()
            ->actingAs(UserFactory::createOne(['companies' => [$this->company]]))
            ->visit('/products/edit/' . $product->getId()->toString())
            ->assertSuccessful()
            ->fillField('product[name]', 'New name')
            ->fillField('product[price]', '12.5')
            ->click('Save')
            ->assertOn('/products/')
            ->assertContains('Product successfully saved');

        $this->em->clear();
        $reloaded = $this->em->find(Product::class, $product->getId());
        self::assertInstanceOf(Product::class, $reloaded);
        self::assertSame('New name', $reloaded->getName());
        self::assertTrue($reloaded->getPrice()->isEqualTo(BigDecimal::of('12.5')));
    }

    public function testEditOtherCompanyProductIs404(): void
    {
        $user = UserFactory::createOne(['companies' => [$this->company]]);

        $filters = $this->em->getFilters();
        $filters->disable('company');

        $foreign = ProductFactory::createOne(['company' => CompanyFactory::createOne(['name' => 'Other Inc'])]);
        $filters->enable('company');
        $id = $foreign->getId()->toString();
        $this->em->clear();

        $this->browser()
            ->actingAs($user)
            ->visit('/products/edit/' . $id)
            ->assertStatus(404);
    }
}
