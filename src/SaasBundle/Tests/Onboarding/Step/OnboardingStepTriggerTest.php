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
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Onboarding\Step\FeedbackStep;
use SolidInvoice\SaasBundle\Onboarding\Step\FreeFeedbackStep;
use SolidInvoice\SaasBundle\Onboarding\Step\SendFirstInvoiceStep;
use SolidInvoice\SaasBundle\Onboarding\Step\TrialAboutToEndStep;
use SolidInvoice\SaasBundle\Onboarding\Step\TurnInvoicesIntoPaymentsStep;
use SolidInvoice\SaasBundle\Onboarding\Step\UpgradeOfferStep;
use SolidInvoice\SaasBundle\Onboarding\Step\VerifyEmailFinalReminderStep;
use SolidInvoice\SaasBundle\Onboarding\Step\VerifyEmailReminderStep;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Model\UserInterface;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Each onboarding email is sent when its condition first holds, so the
 * condition is the trigger. Trial: 2026-01-01 to 2026-01-15.
 */
#[CoversClass(FeedbackStep::class)]
#[CoversClass(FreeFeedbackStep::class)]
#[CoversClass(SendFirstInvoiceStep::class)]
#[CoversClass(TurnInvoicesIntoPaymentsStep::class)]
#[CoversClass(VerifyEmailReminderStep::class)]
#[CoversClass(VerifyEmailFinalReminderStep::class)]
#[CoversClass(TrialAboutToEndStep::class)]
#[CoversClass(UpgradeOfferStep::class)]
final class OnboardingStepTriggerTest extends TestCase
{
    public function testFirstInvoiceNudgeWaitsADay(): void
    {
        $step = $this->sendFirstInvoice(issued: 0, verified: true);

        self::assertFalse($step->shouldSend($this->context('2026-01-01 23:00')));
        self::assertTrue($step->shouldSend($this->context('2026-01-02 00:00')));
    }

    public function testFirstInvoiceNudgeIsNotSuppressedByADraftButIsByAnIssuedInvoice(): void
    {
        // countIssued() excludes drafts; InvoiceRepositoryTest covers that half.
        self::assertFalse($this->sendFirstInvoice(issued: 1, verified: true)->shouldSend($this->context('2026-01-03')));
    }

    public function testFirstInvoiceNudgeWaitsForAnUnverifiedUserToVerify(): void
    {
        self::assertFalse($this->sendFirstInvoice(issued: 0, verified: false)->shouldSend($this->context('2026-01-03')));
    }

    public function testPaymentsEmailFollowsTheFirstIssuedInvoice(): void
    {
        self::assertFalse($this->turnInvoicesIntoPayments(issued: 0, payments: 0)->shouldSend($this->context('2026-01-02')));
        self::assertTrue($this->turnInvoicesIntoPayments(issued: 1, payments: 0)->shouldSend($this->context('2026-01-02')));
        self::assertFalse($this->turnInvoicesIntoPayments(issued: 1, payments: 1)->shouldSend($this->context('2026-01-02')));
    }

    public function testFeedbackIsAskedForAWeekInOnceAnInvoiceHasBeenSent(): void
    {
        $sent = new FeedbackStep($this->createStub(TranslatorInterface::class), $this->invoices(1));
        $none = new FeedbackStep($this->createStub(TranslatorInterface::class), $this->invoices(0));

        self::assertFalse($sent->shouldSend($this->context('2026-01-07 23:00')));
        self::assertTrue($sent->shouldSend($this->context('2026-01-08 00:00')));
        self::assertFalse($none->shouldSend($this->context('2026-01-10')));
    }

    public function testFreeFeedbackIsAskedForAMonthAfterMovingToFree(): void
    {
        $step = new FreeFeedbackStep($this->createStub(TranslatorInterface::class));

        // context() starts the subscription on 2026-01-01, here the day it moved to Free.
        self::assertFalse($step->shouldSend($this->context('2026-01-30 23:00', free: true)));
        self::assertTrue($step->shouldSend($this->context('2026-01-31 00:00', free: true)));
        self::assertFalse($step->shouldSend($this->context('2026-02-15')), 'still on the trial');
    }

    public function testVerifyRemindersGoToUnverifiedUsersAt24And72Hours(): void
    {
        $first = new VerifyEmailReminderStep($this->createStub(TranslatorInterface::class), $this->gate(verified: false));
        $final = new VerifyEmailFinalReminderStep($this->createStub(TranslatorInterface::class), $this->gate(verified: false));

        self::assertFalse($first->shouldSend($this->context('2026-01-01 23:00')));
        self::assertTrue($first->shouldSend($this->context('2026-01-02 00:00')));
        self::assertFalse($final->shouldSend($this->context('2026-01-03 23:00')));
        self::assertTrue($final->shouldSend($this->context('2026-01-04 00:00')));
    }

    public function testVerifyRemindersSkipVerifiedUsers(): void
    {
        $step = new VerifyEmailReminderStep($this->createStub(TranslatorInterface::class), $this->gate(verified: true));

        self::assertFalse($step->shouldSend($this->context('2026-01-05')));
    }

    public function testTrialEndingEmailGoesOutFourDaysBeforeTheEndInBothBillingModes(): void
    {
        foreach ([false, true] as $paidTrial) {
            $step = $this->trialAboutToEnd($paidTrial);

            self::assertFalse($step->shouldSend($this->context('2026-01-10 23:00')));
            self::assertTrue($step->shouldSend($this->context('2026-01-11 00:00')));
        }
    }

    public function testTheGiftEmailGoesOutTwoDaysBeforeTheEndWhenTheCodeCanBeUsed(): void
    {
        $step = new UpgradeOfferStep($this->createStub(TranslatorInterface::class), BillingModeFactory::freeTrial('SAVE30'));

        self::assertFalse($step->shouldSend($this->context('2026-01-12 23:00')));
        self::assertTrue($step->shouldSend($this->context('2026-01-13 00:00')));
        self::assertFalse($step->shouldSend($this->context('2026-01-13', subscriptionId: 'ls_123')), 'a card was added');
        self::assertFalse(new UpgradeOfferStep($this->createStub(TranslatorInterface::class), BillingModeFactory::paidTrial('SAVE30'))->shouldSend($this->context('2026-01-13')), 'card mode');
        self::assertFalse(new UpgradeOfferStep($this->createStub(TranslatorInterface::class), BillingModeFactory::freeTrial(''))->shouldSend($this->context('2026-01-13')), 'no code configured');
    }

    public function testTrialEndingEmailWarnsOfTheChargeWhenACardIsOnFile(): void
    {
        $free = $this->trialAboutToEnd(paidTrial: false)->createEmail($this->context('2026-01-11'));
        $paid = $this->trialAboutToEnd(paidTrial: true)->createEmail($this->context('2026-01-11'));
        $addedCard = $this->trialAboutToEnd(paidTrial: false)->createEmail($this->context('2026-01-11', subscriptionId: 'ls_123'));

        self::assertSame('onboarding.trial_about_to_end.subject', $free->getSubject());
        self::assertFalse($free->getContext()['will_charge']);
        self::assertSame('onboarding.trial_about_to_end.charged.subject', $paid->getSubject());
        self::assertTrue($paid->getContext()['will_charge']);
        self::assertTrue($addedCard->getContext()['will_charge']);
        self::assertSame('', $addedCard->getContext()['coupon_code']);
    }

    private function sendFirstInvoice(int $issued, bool $verified): SendFirstInvoiceStep
    {
        return new SendFirstInvoiceStep($this->createStub(TranslatorInterface::class), $this->invoices($issued), $this->gate($verified));
    }

    private function turnInvoicesIntoPayments(int $issued, int $payments): TurnInvoicesIntoPaymentsStep
    {
        $paymentRepository = $this->createStub(PaymentRepository::class);
        $paymentRepository->method('count')->willReturn($payments);

        return new TurnInvoicesIntoPaymentsStep($this->createStub(TranslatorInterface::class), $this->invoices($issued), $paymentRepository);
    }

    private function trialAboutToEnd(bool $paidTrial): TrialAboutToEndStep
    {
        // Echo the key back so the test can tell which copy was chosen.
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new TrialAboutToEndStep(
            $translator,
            $this->createStub(ClientRepository::class),
            $this->invoices(0),
            $paidTrial ? BillingModeFactory::paidTrial('SAVE30') : BillingModeFactory::freeTrial('SAVE30'),
        );
    }

    private function invoices(int $issued): InvoiceRepository
    {
        $invoiceRepository = $this->createStub(InvoiceRepository::class);
        $invoiceRepository->method('countIssued')->willReturn($issued);

        return $invoiceRepository;
    }

    private function gate(bool $verified): EmailVerificationGateInterface
    {
        return new readonly class($verified) implements EmailVerificationGateInterface {
            public function __construct(
                private bool $verified,
            ) {
            }

            public function isGated(?UserInterface $user = null): bool
            {
                return ! $this->verified;
            }

            public function reason(string $action): string
            {
                return '';
            }
        };
    }

    private function context(string $now, ?string $subscriptionId = null, bool $free = false): OnboardingContext
    {
        $plan = new Plan();
        $plan->setName($free ? 'Free' : 'Pro');
        $plan->setPrice($free ? 0 : 1900);
        $plan->setPlanId($free ? '0' : 'pro');

        $subscription = new Subscription();
        $subscription->setSubscriptionId($subscriptionId);
        $subscription->setStatus($free ? SubscriptionStatus::ACTIVE : SubscriptionStatus::TRIAL);

        return new OnboardingContext(
            new User(),
            new Company(),
            $subscription,
            $plan,
            CarbonImmutable::parse('2026-01-01 00:00'),
            CarbonImmutable::parse('2026-01-15 00:00'),
            new DateTimeImmutable($now),
        );
    }
}
