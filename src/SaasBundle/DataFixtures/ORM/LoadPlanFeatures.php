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

namespace SolidInvoice\SaasBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureManager;

/**
 * Seeds per-plan feature overrides for the four canonical plans, applying the
 * proposed quotas/booleans from the SaaS pricing matrix.
 *
 * Quota cases use -1 as the unlimited sentinel (FeatureValue::UNLIMITED).
 *
 * @codeCoverageIgnore
 */
final class LoadPlanFeatures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var array<string, array<string, int|bool>>
     */
    private const array MATRIX = [
        LoadPlans::REF_FREE => [
            Feature::TotalClients->value => 5,
            Feature::InvoicesPerMonth->value => 10,
            Feature::TeamSeats->value => 1,
            Feature::Quotes->value => false,
            Feature::OnlinePayments->value => false,
            Feature::RecurringInvoices->value => false,
            Feature::AutomatedReminders->value => false,
            Feature::MultiCurrency->value => false,
            Feature::CustomBranding->value => false,
            Feature::RestApiAccess->value => false,
            Feature::McpAccess->value => false,
            Feature::CustomDomain->value => false,
            Feature::CustomFields->value => false,
        ],
        LoadPlans::REF_SOLO => [
            Feature::TotalClients->value => 25,
            Feature::InvoicesPerMonth->value => 100,
            Feature::TeamSeats->value => 1,
            Feature::Quotes->value => true,
            Feature::OnlinePayments->value => true,
            Feature::RecurringInvoices->value => true,
            Feature::AutomatedReminders->value => false,
            Feature::MultiCurrency->value => false,
            Feature::CustomBranding->value => false,
            Feature::RestApiAccess->value => false,
            Feature::McpAccess->value => false,
            Feature::CustomDomain->value => false,
            Feature::CustomFields->value => false,
        ],
        LoadPlans::REF_BUSINESS => [
            Feature::TotalClients->value => 100,
            Feature::InvoicesPerMonth->value => -1,
            Feature::TeamSeats->value => 5,
            Feature::Quotes->value => true,
            Feature::OnlinePayments->value => true,
            Feature::RecurringInvoices->value => true,
            Feature::AutomatedReminders->value => true,
            Feature::MultiCurrency->value => true,
            Feature::CustomBranding->value => true,
            Feature::RestApiAccess->value => true,
            Feature::McpAccess->value => true,
            Feature::CustomDomain->value => false,
            Feature::CustomFields->value => true,
        ],
        LoadPlans::REF_AGENCY => [
            Feature::TotalClients->value => -1,
            Feature::InvoicesPerMonth->value => -1,
            Feature::TeamSeats->value => -1,
            Feature::Quotes->value => true,
            Feature::OnlinePayments->value => true,
            Feature::RecurringInvoices->value => true,
            Feature::AutomatedReminders->value => true,
            Feature::MultiCurrency->value => true,
            Feature::CustomBranding->value => true,
            Feature::RestApiAccess->value => true,
            Feature::McpAccess->value => true,
            Feature::CustomDomain->value => true,
            Feature::CustomFields->value => true,
        ],
    ];

    public function __construct(
        private readonly PlanFeatureManager $planFeatureManager,
    ) {
    }

    public function getDependencies(): array
    {
        return [LoadPlans::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::MATRIX as $planReference => $features) {
            $plan = $this->getReference($planReference, Plan::class);

            foreach ($features as $featureKey => $value) {
                $this->planFeatureManager->setFeature($plan, $featureKey, $value);
            }
        }
    }
}
