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

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\Test\SaasKernel;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\PlanFeature;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;

/**
 * Runs the Products API against the real FeatureGate, with the feature coming from the
 * company's plan rows rather than a stub: a paid plan with `rest_api_access` reaches the
 * API, and only the plan that also carries `products` gets past the products gate.
 */
#[Group('functional')]
final class ProductsApiPlanGateTest extends ApiTestCase
{
    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    #[Override]
    protected function getResourceClass(): string
    {
        return Product::class;
    }

    private mixed $previousPlatform = null;

    protected function setUp(): void
    {
        $this->previousPlatform = $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null;
        $_SERVER['SOLIDINVOICE_PLATFORM'] = $_ENV['SOLIDINVOICE_PLATFORM'] = 'saas';
        parent::setUp();
    }

    #[After]
    public function resetPlatform(): void
    {
        if ($this->previousPlatform === null) {
            unset($_SERVER['SOLIDINVOICE_PLATFORM'], $_ENV['SOLIDINVOICE_PLATFORM']);
        } else {
            $_SERVER['SOLIDINVOICE_PLATFORM'] = $_ENV['SOLIDINVOICE_PLATFORM'] = $this->previousPlatform;
        }
    }

    public function testPlanWithProductsReachesTheApi(): void
    {
        $this->subscribe(restApi: true, products: true);

        $this->requestGetCollection('/api/products');
    }

    public function testPlanWithoutProductsIsForbidden(): void
    {
        $this->subscribe(restApi: true, products: false);

        self::$client->request('GET', '/api/products', ['headers' => ['accept' => 'application/ld+json']]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testPlanWithoutProductsStillReachesOtherResources(): void
    {
        $this->subscribe(restApi: true, products: false);

        $this->requestGetCollection('/api/taxes');
    }

    private function subscribe(bool $restApi, bool $products): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $company = $em->find(Company::class, $this->company->getId());

        $plan = new Plan()->setName('Gate')->setPlanId('gate-' . (int) $products)->setPrice(1000)->setActive(true);
        $em->persist($plan);

        foreach ([[Feature::RestApiAccess, $restApi], [Feature::Products, $products]] as [$feature, $enabled]) {
            $em->persist(new PlanFeature()
                ->setPlan($plan)
                ->setFeatureKey($feature->value)
                ->setType($feature->getType())
                ->setValue($enabled));
        }

        $em->persist(new Subscription()
            ->setSubscriber($company)
            ->setPlan($plan)
            ->setStatus(SubscriptionStatus::ACTIVE)
            ->setStartDate(CarbonImmutable::now()->subDay())
            ->setEndDate(CarbonImmutable::now()->addYear()));
        $em->flush();
    }
}
