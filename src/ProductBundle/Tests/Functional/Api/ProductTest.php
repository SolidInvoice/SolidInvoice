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

namespace SolidInvoice\ProductBundle\Tests\Functional\Api;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class ProductTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Product::class;
    }

    public function testCreate(): void
    {
        $tax = TaxFactory::createOne();

        $result = $this->requestPost('/api/products', [
            'name' => 'Consulting',
            'description' => 'One hour of consulting',
            'price' => 4500.5,
            'quantity' => 2,
            'tax' => $this->getIriFromResource($tax),
        ]);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        unset($result['id'], $result['@id']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource(Product::class),
            '@type' => 'https://schema.org/Product',
            'name' => 'Consulting',
            'description' => 'One hour of consulting',
            'price' => 4500.5,
            'quantity' => 2,
            'tax' => $this->getIriFromResource($tax),
        ], $result);
    }

    public function testCreateWithoutTaxReturnsNullTax(): void
    {
        $result = $this->requestPost('/api/products', ['name' => 'Widget', 'price' => 10, 'quantity' => 1]);

        self::assertArrayHasKey('tax', $result);
        self::assertNull($result['tax']);
        self::assertNull($result['description']);
    }

    public function testGet(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Widget',
            'price' => BigDecimal::of('12.34'),
            'quantity' => BigDecimal::of('3'),
        ]);

        $data = $this->requestGet($this->getIriFromResource($product));

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($product),
            '@id' => $this->getIriFromResource($product),
            '@type' => 'https://schema.org/Product',
            'id' => $product->getId()->toString(),
            'name' => 'Widget',
            'description' => null,
            'price' => 12.34,
            'quantity' => 3,
            'tax' => null,
        ], $data);
    }

    public function testEdit(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Old',
            'price' => BigDecimal::of('5'),
        ]);

        $data = $this->requestPatch($this->getIriFromResource($product), ['name' => 'New', 'price' => 7.25]);

        self::assertSame('New', $data['name']);
        self::assertEquals(7.25, $data['price']);
        self::assertEquals(1, $data['quantity']);
    }

    public function testDelete(): void
    {
        $product = ProductFactory::createOne();

        $this->requestDelete($this->getIriFromResource($product));
    }

    public function testGetCollection(): void
    {
        ProductFactory::createMany(3);

        $data = $this->requestGetCollection('/api/products');

        self::assertArraySubset([
            '@context' => $this->getContextForResource(Product::class),
            '@id' => '/api/products',
            '@type' => 'Collection',
        ], $data);
        self::assertCount(3, $data['member']);
    }

    public function testCannotAccessProductFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $product = ProductFactory::createOne(['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($product), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * The company filter hides another company's tax, so its IRI never resolves and the
     * product is never built: a 400 here is the cross-tenant guard, there is no tax to validate.
     */
    public function testCannotAssignTaxFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $tax = TaxFactory::createOne(['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('POST', '/api/products', [
            'json' => ['name' => 'Widget', 'price' => 1, 'quantity' => 1, 'tax' => $this->getIriFromResource($tax)],
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertCount(0, ProductFactory::all());
    }

    /**
     * @return iterable<string, array{string, int|float|string, string}>
     */
    public static function invalidAmountProvider(): iterable
    {
        yield 'price too large' => ['price', '10000000000000', 'price'];
        yield 'price too many decimals' => ['price', '1.0000001', 'price'];
        yield 'negative price' => ['price', -1, 'price'];
        yield 'quantity too large' => ['quantity', '100000000000000', 'quantity'];
        yield 'zero quantity' => ['quantity', 0, 'quantity'];
    }

    #[DataProvider('invalidAmountProvider')]
    public function testRejectsAmountsThatDoNotFitTheColumn(string $field, int | float | string $value, string $path): void
    {
        $payload = ['name' => 'Widget', 'price' => 1, 'quantity' => 1, $field => $value];

        $response = self::$client->request('POST', '/api/products', [
            'body' => json_encode($payload, JSON_PRESERVE_ZERO_FRACTION),
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($path, array_column($response->toArray(false)['violations'], 'propertyPath'));
    }
}
