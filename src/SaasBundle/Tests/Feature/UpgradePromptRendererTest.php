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

namespace SolidInvoice\SaasBundle\Tests\Feature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\SaasBundle\Feature\FeatureUsage;
use SolidInvoice\SaasBundle\Feature\UpgradePromptRenderer;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureValue;
use SolidWorx\Platform\PlatformBundle\Feature\PlanReference;
use SolidWorx\Platform\PlatformBundle\Feature\UpgradeOptions;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[CoversClass(UpgradePromptRenderer::class)]
final class UpgradePromptRendererTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testLowestPlanForReturnsNullWhenUpgradeOptionsAreEmpty(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('foo')->andReturn(new UpgradeOptions([]));

        $renderer = $this->makeRenderer(gate: $gate);

        self::assertNull($renderer->lowestPlanFor('foo'));
    }

    public function testLowestPlanForPicksTheCheapestPlan(): void
    {
        $solo = $this->makePlan('Solo', 900);
        $business = $this->makePlan('Business', 1900);
        $agency = $this->makePlan('Agency', 3900);

        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('mcp_access')->andReturn(new UpgradeOptions([
            new PlanReference($business->getId()->toBase58(), $business->getName()),
            new PlanReference($solo->getId()->toBase58(), $solo->getName()),
            new PlanReference($agency->getId()->toBase58(), $agency->getName()),
        ]));

        $repo = M::mock(PlanRepositoryInterface::class);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($business->getId())))->andReturn($business);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($solo->getId())))->andReturn($solo);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($agency->getId())))->andReturn($agency);

        $renderer = $this->makeRenderer(gate: $gate, repo: $repo);

        $cheapest = $renderer->lowestPlanFor('mcp_access');

        self::assertInstanceOf(Plan::class, $cheapest);
        self::assertSame('Solo', $cheapest->getName());
    }

    public function testLowestPlanForSkipsUnknownPlanReferences(): void
    {
        $solo = $this->makePlan('Solo', 900);

        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('mcp_access')->andReturn(new UpgradeOptions([
            new PlanReference(Ulid::generate(), 'Phantom'),
            new PlanReference($solo->getId()->toBase58(), $solo->getName()),
        ]));

        $repo = M::mock(PlanRepositoryInterface::class);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => ! $id->equals($solo->getId())))->andReturn(null);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($solo->getId())))->andReturn($solo);

        $renderer = $this->makeRenderer(gate: $gate, repo: $repo);

        self::assertSame('Solo', $renderer->lowestPlanFor('mcp_access')?->getName());
    }

    public function testLowestPlanForReturnsNullForMalformedPlanReferenceId(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('mcp_access')->andReturn(new UpgradeOptions([
            new PlanReference('not-a-base58-ulid!', 'Bogus'),
        ]));

        $repo = M::mock(PlanRepositoryInterface::class);

        $renderer = $this->makeRenderer(gate: $gate, repo: $repo);

        self::assertNull($renderer->lowestPlanFor('mcp_access'));
    }

    public function testLowestPlanForSkipsFreePlan(): void
    {
        $free = $this->makeFreePlan();
        $solo = $this->makePlan('Solo', 900);

        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('quotes')->andReturn(new UpgradeOptions([
            new PlanReference($free->getId()->toBase58(), $free->getName()),
            new PlanReference($solo->getId()->toBase58(), $solo->getName()),
        ]));

        $repo = M::mock(PlanRepositoryInterface::class);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($free->getId())))->andReturn($free);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($solo->getId())))->andReturn($solo);

        $renderer = $this->makeRenderer(gate: $gate, repo: $repo);

        // Even though Free is cheaper (price 0), Solo is the lowest *paid* plan.
        self::assertSame('Solo', $renderer->lowestPlanFor('quotes')?->getName());
    }

    public function testLowestPlanForReturnsNullWhenOnlyFreeOffersTheFeature(): void
    {
        $free = $this->makeFreePlan();

        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('quotes')->andReturn(new UpgradeOptions([
            new PlanReference($free->getId()->toBase58(), $free->getName()),
        ]));

        $repo = M::mock(PlanRepositoryInterface::class);
        $repo->shouldReceive('find')->with(M::on(static fn (Ulid $id) => $id->equals($free->getId())))->andReturn($free);

        $renderer = $this->makeRenderer(gate: $gate, repo: $repo);

        self::assertNull($renderer->lowestPlanFor('quotes'));
    }

    public function testUsageBannerReturnsEmptyForUnlimitedFeature(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('resolve')->with('total_clients')
            ->andReturn(new FeatureValue('total_clients', FeatureType::INTEGER, FeatureValue::UNLIMITED));

        $renderer = $this->makeRenderer(gate: $gate);

        self::assertSame('', $renderer->usageBanner('total_clients', 9999));
    }

    public function testUsageBannerReturnsEmptyWhenNotApproachingLimit(): void
    {
        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('resolve')->with('total_clients')
            ->andReturn(new FeatureValue('total_clients', FeatureType::INTEGER, 100));
        $gate->shouldReceive('remaining')->with('total_clients', 50)->andReturn(50);

        $renderer = $this->makeRenderer(gate: $gate);

        self::assertSame('', $renderer->usageBanner('total_clients', 50));
    }

    public function testMenuLabelReturnsPlanName(): void
    {
        $solo = $this->makePlan('Solo', 900);

        $gate = M::mock(FeatureGate::class);
        $gate->shouldReceive('upgradeOptions')->with('mcp_access')->andReturn(new UpgradeOptions([
            new PlanReference($solo->getId()->toBase58(), $solo->getName()),
        ]));

        $repo = M::mock(PlanRepositoryInterface::class);
        $repo->shouldReceive('find')->andReturn($solo);

        $renderer = $this->makeRenderer(gate: $gate, repo: $repo);

        self::assertSame('Solo', $renderer->menuLabel('mcp_access'));
    }

    private function makeRenderer(
        ?FeatureGate $gate = null,
        ?PlanRepositoryInterface $repo = null,
    ): UpgradePromptRenderer {
        $twig = M::mock(Environment::class);
        $translator = M::mock(TranslatorInterface::class);

        return new UpgradePromptRenderer(
            $gate ?? M::mock(FeatureGate::class),
            $repo ?? M::mock(PlanRepositoryInterface::class),
            $twig,
            $translator,
            new FeatureUsage(),
        );
    }

    private function makePlan(string $name, int $price): Plan
    {
        $plan = new Plan();
        $plan->setName($name);
        $plan->setPrice($price);
        $plan->setPlanId('plan-' . strtolower($name));

        // Force a real ULID so toBase58() round-trips through Ulid::fromBase58().
        $reflection = new ReflectionProperty(Plan::class, 'id');
        $reflection->setValue($plan, new Ulid());

        return $plan;
    }

    private function makeFreePlan(): Plan
    {
        // Plan::isFree() requires both price === 0 AND planId === '0'.
        $plan = new Plan();
        $plan->setName('Free');
        $plan->setPrice(0);
        $plan->setPlanId('0');

        $reflection = new ReflectionProperty(Plan::class, 'id');
        $reflection->setValue($plan, new Ulid());

        return $plan;
    }
}
