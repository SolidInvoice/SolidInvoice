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

namespace SolidInvoice\SaasBundle\Tests\Trial;

use Carbon\CarbonImmutable;
use DateInterval;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\SaasBundle\Trial\TrialOffer;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureManager;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;
use Symfony\Component\Clock\MockClock;
use function strtolower;

#[CoversClass(TrialOffer::class)]
final class TrialOfferTest extends TestCase
{
    public function testOnlyAUserWithoutATrialIsEligible(): void
    {
        self::assertTrue($this->offer(userHasTrial: false)->isEligible(new User()));
        self::assertFalse($this->offer(userHasTrial: true)->isEligible(new User()));
    }

    /**
     * The trial runs on the cheapest plan that has the feature, not the top one.
     */
    public function testTheTrialRunsOnTheCheapestPlanWithTheFeature(): void
    {
        $solo = $this->plan('Solo', 900, 'P14D');
        $business = $this->plan('Business', 3900, 'P30D');

        $plan = $this->offer([$business, $solo])->planForFeature('automated_reminders', $this->onPlan($this->free()));

        self::assertSame($solo, $plan);
    }

    public function testPlansWithoutATrialLengthOrThatAreInactiveAreSkipped(): void
    {
        $noTrial = $this->plan('Solo', 900, null);
        $zeroTrial = $this->plan('Lite', 500, 'P0D');
        $inactive = $this->plan('Legacy', 700, 'P14D')->setActive(false);
        $pro = $this->plan('Pro', 1900, 'P30D');

        $plan = $this->offer([$noTrial, $zeroTrial, $inactive, $pro])->planForFeature('automated_reminders', $this->onPlan($this->free()));

        self::assertSame($pro, $plan);
    }

    /**
     * A limit gate on a trial plan must find the next tier up, not the plan the
     * trial is already on.
     */
    public function testOnlyPlansAboveTheCurrentOneAreOffered(): void
    {
        $solo = $this->plan('Solo', 900, 'P14D');
        $pro = $this->plan('Pro', 1900, 'P30D');

        self::assertSame($pro, $this->offer([$solo, $pro])->planForFeature('total_clients', $this->onPlan($solo)));
        self::assertNull($this->offer([$solo])->planForFeature('total_clients', $this->onPlan($solo)));
    }

    public function testAPlanAskedForByIdMustBeTrialable(): void
    {
        $pro = $this->plan('Pro', 1900, 'P30D');

        self::assertSame($pro, $this->offer(lookup: $pro)->planById('pro', $this->onPlan($this->free())));
        self::assertNull($this->offer(lookup: $this->plan('Solo', 900, null))->planById('solo', $this->onPlan($this->free())));
        self::assertNull($this->offer()->planById('missing', $this->onPlan($this->free())));
    }

    /**
     * API/MCP access is denied to trials, and the custom domain is locked during
     * them, so no trial is offered even when a plan with a trial has them.
     */
    public function testFeaturesATrialCannotUseAreNeverOffered(): void
    {
        $business = $this->plan('Business', 3900, 'P30D');

        foreach (['rest_api_access', 'mcp_access', 'custom_domain', 'not_a_feature'] as $feature) {
            self::assertNull($this->offer([$business])->planForFeature($feature, $this->onPlan($this->free())), $feature);
        }
    }

    public function testAnEligibleUserIsOfferedAStart(): void
    {
        $pro = $this->plan('Pro', 1900, 'P30D');

        $offer = $this->offer()->offer($pro, new User(), $this->onPlan($this->free()));

        self::assertNotNull($offer);
        self::assertFalse($offer->movingUp);
        self::assertSame(30, $offer->days);
    }

    /**
     * A running trial only moves up, whoever clicks, so its end date is never reset.
     */
    public function testARunningTrialIsOfferedAMoveUpEvenToAUserWhoHadATrial(): void
    {
        $running = $this->onPlan($this->plan('Solo', 900, 'P14D'))
            ->setStatus(SubscriptionStatus::TRIAL)
            ->setEndDate(CarbonImmutable::parse('2026-01-10'));

        $offer = $this->offer(userHasTrial: true, now: '2026-01-05')->offer($this->plan('Business', 3900, 'P30D'), new User(), $running);

        self::assertNotNull($offer);
        self::assertTrue($offer->movingUp);
    }

    /**
     * A card was added during the trial, so Lemon Squeezy bills it. Moving it up
     * here would change the plan locally but not in Lemon Squeezy.
     */
    public function testABilledRunningTrialIsNotOfferedAMoveUp(): void
    {
        $running = $this->onPlan($this->plan('Solo', 900, 'P14D'))
            ->setStatus(SubscriptionStatus::TRIAL)
            ->setEndDate(CarbonImmutable::parse('2026-01-10'))
            ->setSubscriptionId('ls-123');

        self::assertNull($this->offer(now: '2026-01-05')->offer($this->plan('Business', 3900, 'P30D'), new User(), $running));
    }

    public function testNoOfferForAUserWhoHadATrialOrABilledSubscription(): void
    {
        $pro = $this->plan('Pro', 1900, 'P30D');

        self::assertNull($this->offer(userHasTrial: true)->offer($pro, new User(), $this->onPlan($this->free())));
        self::assertNull($this->offer()->offer($pro, new User(), $this->onPlan($this->free())->setSubscriptionId('ls-123')));
    }

    /**
     * The trial table allows one row per subscription, so a company that already
     * had its trial (started by another member) cannot take a second one.
     */
    public function testNoOfferOnASubscriptionThatAlreadyHadATrial(): void
    {
        $pro = $this->plan('Pro', 1900, 'P30D');

        self::assertNull($this->offer(subscriptionHadTrial: true)->offer($pro, new User(), $this->onPlan($this->free())));
    }

    /**
     * In paid-trial mode a trial starts at checkout with a card; gates must not
     * hand out a no-card one.
     */
    public function testNoOfferWhenTrialsNeedACard(): void
    {
        $pro = $this->plan('Pro', 1900, 'P30D');

        self::assertNull($this->offer(billingMode: BillingModeFactory::paidTrial())->offer($pro, new User(), $this->onPlan($this->free())));
    }

    public function testATrialIsRunningUntilItsEndDate(): void
    {
        $subscription = $this->onPlan($this->plan('Pro', 1900, 'P30D'))
            ->setStatus(SubscriptionStatus::TRIAL)
            ->setEndDate(CarbonImmutable::parse('2026-01-10'));

        self::assertTrue($this->offer(now: '2026-01-09')->isRunning($subscription));
        self::assertFalse($this->offer(now: '2026-01-11')->isRunning($subscription));
        self::assertFalse($this->offer(now: '2026-01-09')->isRunning($subscription->setStatus(SubscriptionStatus::ACTIVE)));
    }

    /**
     * @param list<Plan> $plansWithFeature
     */
    private function offer(array $plansWithFeature = [], ?Plan $lookup = null, bool $userHasTrial = false, string $now = '2026-01-01', bool $subscriptionHadTrial = false, ?BillingMode $billingMode = null): TrialOffer
    {
        $trialManager = $this->createStub(TrialManagerInterface::class);
        $trialManager->method('userHasTrial')->willReturn($userHasTrial);

        $featureManager = $this->createStub(PlanFeatureManager::class);
        $featureManager->method('findPlansWithFeature')->willReturn($plansWithFeature);

        $planRepository = $this->createStub(PlanRepositoryInterface::class);
        $planRepository->method('find')->willReturn($lookup);

        $trialRepository = $this->createStub(ObjectRepository::class);
        $trialRepository->method('findOneBy')->willReturn($subscriptionHadTrial ? new Trial() : null);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($trialRepository);

        return new TrialOffer($trialManager, $featureManager, $planRepository, new TrialPeriod(), new MockClock($now), $registry, $billingMode ?? BillingModeFactory::freeTrial());
    }

    private function plan(string $name, int $price, ?string $trial): Plan
    {
        $plan = new Plan()->setName($name)->setPlanId(strtolower($name))->setPrice($price)->setActive(true);

        if ($trial !== null) {
            $plan->setTrialDuration(new DateInterval($trial));
        }

        return $plan;
    }

    private function free(): Plan
    {
        return new Plan()->setName('Free')->setPlanId('0')->setPrice(0)->setActive(true);
    }

    private function onPlan(Plan $plan): Subscription
    {
        return new Subscription()->setPlan($plan)->setStatus(SubscriptionStatus::ACTIVE);
    }
}
