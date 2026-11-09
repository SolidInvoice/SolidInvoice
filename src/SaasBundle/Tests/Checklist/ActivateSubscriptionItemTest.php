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

namespace SolidInvoice\SaasBundle\Tests\Checklist;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\SaasBundle\Checklist\ActivateSubscriptionItem;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(ActivateSubscriptionItem::class)]
final class ActivateSubscriptionItemTest extends TestCase
{
    public function testTheItemIsOfferedInFreeTrialMode(): void
    {
        self::assertTrue($this->item(BillingModeFactory::freeTrial())->active());
    }

    /**
     * In paid-trial mode the company cannot reach the dashboard without an
     * active subscription, so the prompt would always be redundant.
     */
    public function testTheItemIsHiddenInPaidTrialMode(): void
    {
        self::assertFalse($this->item(BillingModeFactory::paidTrial())->active());
    }

    /**
     * Free is forever, so "activate your subscription" is a wrong nag there.
     */
    public function testTheItemIsHiddenForAnActiveFreeSubscription(): void
    {
        self::assertFalse($this->item(BillingModeFactory::freeTrial(), $this->subscription(SubscriptionStatus::ACTIVE, free: true))->active());
    }

    public function testTheItemIsOfferedDuringATrial(): void
    {
        self::assertTrue($this->item(BillingModeFactory::freeTrial(), $this->subscription(SubscriptionStatus::TRIAL, free: false))->active());
    }

    /**
     * Only Free is hidden: a paid ACTIVE subscription still shows, as done.
     */
    public function testAnActivePaidSubscriptionShowsTheItemAsComplete(): void
    {
        $item = $this->item(BillingModeFactory::freeTrial(), $this->subscription(SubscriptionStatus::ACTIVE, free: false));

        self::assertTrue($item->active());
        self::assertTrue($item->isComplete());
        self::assertFalse($this->item(BillingModeFactory::freeTrial())->isComplete());
    }

    private function item(BillingMode $billingMode, ?Subscription $subscription = null): ActivateSubscriptionItem
    {
        $companySelector = $this->createStub(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn(new Ulid());

        $companyRepository = $this->createStub(CompanyRepository::class);
        $companyRepository->method('find')->willReturn(new Company());

        $subscriptionProvider = $this->createStub(SubscriptionProviderInterface::class);
        $subscriptionProvider->method('getSubscriptionFor')->willReturn($subscription);

        return new ActivateSubscriptionItem(
            $subscriptionProvider,
            $companySelector,
            $companyRepository,
            $billingMode,
        );
    }

    private function subscription(SubscriptionStatus $status, bool $free): Subscription
    {
        $plan = new Plan();
        $plan->setPlanId($free ? '0' : 'variant-123');
        $plan->setPrice($free ? 0 : 1900);

        $subscription = new Subscription();
        $subscription->setPlan($plan);
        $subscription->setStatus($status);

        return $subscription;
    }
}
