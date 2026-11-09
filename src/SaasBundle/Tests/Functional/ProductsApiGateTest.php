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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

/**
 * Asserts that every /api/products operation is refused with 403 when the `products`
 * feature is off, and works normally when it is on.
 */
#[Group('functional')]
final class ProductsApiGateTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Product::class;
    }

    public function testCollectionIsForbiddenWhenFeatureIsOff(): void
    {
        $this->setProductsEnabled(false);

        self::$client->request('GET', '/api/products', ['headers' => ['accept' => 'application/ld+json']]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testPostIsForbiddenWhenFeatureIsOff(): void
    {
        $this->setProductsEnabled(false);

        self::$client->request('POST', '/api/products', [
            'json' => ['name' => 'Widget', 'price' => 1, 'quantity' => 1],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testItemIsForbiddenWhenFeatureIsOff(): void
    {
        $product = ProductFactory::createOne();
        $this->setProductsEnabled(false);

        self::$client->request('GET', $this->getIriFromResource($product), ['headers' => ['accept' => 'application/ld+json']]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testGraphQlQueryIsDeniedWhenFeatureIsOff(): void
    {
        $this->setProductsEnabled(false);
        ProductFactory::createOne(['name' => 'Secret widget']);

        $response = $this->graphQl('{ products { edges { node { id name } } } }');

        self::assertStringNotContainsString('Secret widget', $response);
        self::assertStringContainsString('Access Denied', $response);
    }

    public function testGraphQlMutationIsDeniedWhenFeatureIsOff(): void
    {
        $this->setProductsEnabled(false);

        $response = $this->graphQl('mutation { createProduct(input: {name: "Widget", price: 1, quantity: 1}) { product { id } } }');

        self::assertStringContainsString('Access Denied', $response);
        self::assertCount(0, ProductFactory::all());
    }

    public function testGraphQlQueryWorksWhenFeatureIsOn(): void
    {
        $this->setProductsEnabled(true);
        ProductFactory::createOne(['name' => 'Visible widget']);

        self::assertStringContainsString('Visible widget', $this->graphQl('{ products { edges { node { id name } } } }'));
    }

    public function testCollectionWorksWhenFeatureIsOn(): void
    {
        $this->setProductsEnabled(true);

        $this->requestGetCollection('/api/products');
    }

    public function testOtherResourcesAreNotGated(): void
    {
        $this->setProductsEnabled(false);

        $this->requestGetCollection('/api/taxes');
    }

    private function graphQl(string $query): string
    {
        $response = self::$client->request('POST', '/api/graphql', [
            'json' => ['query' => $query],
            'headers' => ['content-type' => 'application/json', 'accept' => 'application/json'],
        ]);

        return $response->getContent(false);
    }

    /**
     * With the SaaS container the API itself needs a paid subscription (SubscriptionVoter), which
     * would deny these requests before they say anything about the products gate; the plan-driven
     * ProductsApiPlanGateTest covers that mode with the real FeatureGate.
     */
    private function skipInSaasMode(): void
    {
        if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) === 'saas') {
            self::markTestSkipped('The REST API requires a paid subscription in SaaS mode.');
        }
    }

    /**
     * The gate is already initialised by the time ApiTestCase::setUp() finishes, so it can no
     * longer be replaced in that container: boot a fresh one and swap the stub in first.
     */
    private function setProductsEnabled(bool $enabled): void
    {
        $this->skipInSaasMode();

        self::ensureKernelShutdown();
        self::$client = self::createClient();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturnCallback(static fn (string $key): bool => $key !== 'products' || $enabled);
        $featureGate->method('canUse')->willReturnCallback(static fn (string $key): bool => $key !== 'products' || $enabled);
        self::getContainer()->set(FeatureGate::class, $featureGate);

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $em->find(User::class, $this->user->getId());
        $token = self::getContainer()->get(ApiTokenManager::class)->getOrCreate($user, 'Gate Test');
        self::$client->setDefaultOptions(['headers' => ['X-API-TOKEN' => $token->plaintext]]);
    }
}
