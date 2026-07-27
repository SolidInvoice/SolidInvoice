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

namespace SolidInvoice\SaasBundle\Tests\Onboarding\Step;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Onboarding\Step\TrialAboutToEndStep;
use SolidInvoice\SaasBundle\Onboarding\Step\UpgradeOfferStep;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Symfony\Component\Clock\MockClock;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Both of these emails exist to convert a free trial before it lapses. With a
 * card already on file neither has anything to offer, so paid-trial mode must
 * suppress them — while leaving the rest of the onboarding sequence intact.
 */
#[CoversClass(TrialAboutToEndStep::class)]
#[CoversClass(UpgradeOfferStep::class)]
final class TrialIncentiveStepSuppressionTest extends TestCase
{
    public function testTrialAboutToEndSendsInFreeTrialMode(): void
    {
        self::assertTrue(
            $this->trialAboutToEndStep(BillingModeFactory::freeTrial('SAVE30'))->shouldSend($this->context()),
        );
    }

    public function testTrialAboutToEndIsSuppressedInPaidTrialMode(): void
    {
        self::assertFalse(
            $this->trialAboutToEndStep(BillingModeFactory::paidTrial('SAVE30'))->shouldSend($this->context()),
        );
    }

    public function testUpgradeOfferSendsInFreeTrialMode(): void
    {
        self::assertTrue(
            $this->upgradeOfferStep(BillingModeFactory::freeTrial('SAVE30'))->shouldSend($this->context()),
        );
    }

    public function testUpgradeOfferIsSuppressedInPaidTrialMode(): void
    {
        self::assertFalse(
            $this->upgradeOfferStep(BillingModeFactory::paidTrial('SAVE30'))->shouldSend($this->context()),
        );
    }

    private function trialAboutToEndStep(BillingMode $billingMode): TrialAboutToEndStep
    {
        return new TrialAboutToEndStep(
            $this->createStub(TranslatorInterface::class),
            new MockClock('2024-01-05'),
            $this->createStub(ClientRepository::class),
            $this->createStub(InvoiceRepository::class),
            $billingMode,
        );
    }

    private function upgradeOfferStep(BillingMode $billingMode): UpgradeOfferStep
    {
        return new UpgradeOfferStep($this->createStub(TranslatorInterface::class), $billingMode);
    }

    private function context(): OnboardingContext
    {
        return new OnboardingContext(
            new User(),
            new Company(),
            new Subscription(),
            new Plan(),
            CarbonImmutable::parse('2024-01-01'),
            CarbonImmutable::parse('2024-01-15'),
        );
    }
}
