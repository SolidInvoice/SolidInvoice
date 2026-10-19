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

namespace SolidInvoice\SaasBundle\Tests\Email;

use DateTimeImmutable;
use Override;
use ReflectionProperty;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Onboarding\OnboardingEmailStepInterface;
use SolidInvoice\SaasBundle\Onboarding\Step\FeedbackStep;
use SolidInvoice\SaasBundle\Onboarding\Step\SendFirstInvoiceStep;
use SolidInvoice\SaasBundle\Onboarding\Step\TrialAboutToEndStep;
use SolidInvoice\SaasBundle\Onboarding\Step\TurnInvoicesIntoPaymentsStep;
use SolidInvoice\SaasBundle\Onboarding\Step\UpgradeOfferStep;
use SolidInvoice\SaasBundle\Onboarding\Step\VerifyEmailFinalReminderStep;
use SolidInvoice\SaasBundle\Onboarding\Step\VerifyEmailReminderStep;
use SolidInvoice\SaasBundle\Onboarding\Step\WelcomeStep;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class OnboardingEmailSnapshotTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use MatchesSnapshots;

    private const string FROZEN_NOW = '2026-04-19 12:00:00';

    private const string TRIAL_START = '2026-04-01 00:00:00';

    private const string TRIAL_END = '2026-04-22 00:00:00';

    private const string VERIFY_URL = 'https://app.example.test/verify?id=abc&signature=xyz';

    private Environment $twig;

    private TranslatorInterface $translator;

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = self::getContainer()->get('twig');
        $this->translator = self::getContainer()->get(TranslatorInterface::class);
    }

    public function testWelcomeHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(WelcomeStep::class)));
    }

    public function testWelcomeTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(WelcomeStep::class)));
    }

    public function testSendFirstInvoiceHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(SendFirstInvoiceStep::class)));
    }

    public function testSendFirstInvoiceTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(SendFirstInvoiceStep::class)));
    }

    public function testTurnInvoicesIntoPaymentsHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(TurnInvoicesIntoPaymentsStep::class)));
    }

    public function testTurnInvoicesIntoPaymentsTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(TurnInvoicesIntoPaymentsStep::class)));
    }

    public function testFeedbackHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(FeedbackStep::class)));
    }

    public function testFeedbackTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(FeedbackStep::class)));
    }

    public function testUpgradeOfferHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml($this->upgradeOfferStep()));
    }

    public function testUpgradeOfferTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText($this->upgradeOfferStep()));
    }

    public function testTrialAboutToEndHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml($this->trialAboutToEndStep()));
    }

    public function testTrialAboutToEndTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText($this->trialAboutToEndStep()));
    }

    public function testTrialAboutToEndWithCardOnFileHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml($this->trialAboutToEndStep(paidTrial: true)));
    }

    public function testWelcomeForUnverifiedUserHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(new WelcomeStep($this->translator), ['verify_url' => self::VERIFY_URL]));
    }

    public function testVerifyEmailReminderHtmlSnapshot(): void
    {
        $step = new VerifyEmailReminderStep($this->translator, self::getContainer()->get(EmailVerificationGateInterface::class));

        $this->assertMatchesHtmlSnapshot($this->renderHtml($step, ['verify_url' => self::VERIFY_URL]));
    }

    public function testVerifyEmailFinalReminderTextSnapshot(): void
    {
        $step = new VerifyEmailFinalReminderStep($this->translator, self::getContainer()->get(EmailVerificationGateInterface::class));

        $this->assertMatchesTextSnapshot($this->renderText($step, ['verify_url' => self::VERIFY_URL]));
    }

    /**
     * @param array<string, mixed> $extra what the handler adds, such as the verify link
     */
    private function renderHtml(OnboardingEmailStepInterface $step, array $extra = []): string
    {
        $email = $step->createEmail($this->context());

        return $this->twig->render($email->getHtmlTemplate(), $email->getContext() + $extra);
    }

    /**
     * @param array<string, mixed> $extra what the handler adds, such as the verify link
     */
    private function renderText(OnboardingEmailStepInterface $step, array $extra = []): string
    {
        $email = $step->createEmail($this->context());

        return $this->twig->render($email->getTextTemplate(), $email->getContext() + $extra);
    }

    private function context(): OnboardingContext
    {
        $user = new User();
        $user->setFirstName('Alex');
        $user->setEmail('alex@example.test');

        $idProperty = new ReflectionProperty($user, 'id');
        $idProperty->setValue($user, new Ulid());

        $plan = new Plan();
        $plan->setName('SolidInvoice Pro');
        $plan->setPrice(1200);
        $plan->setPlanId('pro-monthly');

        $subscription = new Subscription();
        $subscription->setStartDate(new DateTimeImmutable(self::TRIAL_START));
        $subscription->setEndDate(new DateTimeImmutable(self::TRIAL_END));

        return new OnboardingContext(
            $user,
            $this->company,
            $subscription,
            $plan,
            new DateTimeImmutable(self::TRIAL_START),
            new DateTimeImmutable(self::TRIAL_END),
            new DateTimeImmutable(self::FROZEN_NOW),
        );
    }

    private function trialAboutToEndStep(bool $paidTrial = false): TrialAboutToEndStep
    {
        return new TrialAboutToEndStep(
            $this->translator,
            self::getContainer()->get(ClientRepository::class),
            self::getContainer()->get(InvoiceRepository::class),
            $paidTrial ? BillingModeFactory::paidTrial('SOLID30') : BillingModeFactory::freeTrial('SOLID30'),
        );
    }

    private function upgradeOfferStep(): UpgradeOfferStep
    {
        return new UpgradeOfferStep($this->translator, BillingModeFactory::freeTrial('SOLID30'));
    }
}
