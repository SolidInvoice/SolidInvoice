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
use ReflectionProperty;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Onboarding\OnboardingEmailStepInterface;
use SolidInvoice\SaasBundle\Onboarding\Step\AddFirstClientStep;
use SolidInvoice\SaasBundle\Onboarding\Step\CustomizeLogoStep;
use SolidInvoice\SaasBundle\Onboarding\Step\RecurringBillingStep;
use SolidInvoice\SaasBundle\Onboarding\Step\SendFirstInvoiceStep;
use SolidInvoice\SaasBundle\Onboarding\Step\TrialAboutToEndStep;
use SolidInvoice\SaasBundle\Onboarding\Step\TurnInvoicesIntoPaymentsStep;
use SolidInvoice\SaasBundle\Onboarding\Step\UpgradeOfferStep;
use SolidInvoice\SaasBundle\Onboarding\Step\WelcomeStep;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

final class OnboardingEmailSnapshotTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use MatchesSnapshots;

    private const FROZEN_NOW = '2026-04-19 12:00:00';

    private const TRIAL_START = '2026-04-01 00:00:00';

    private const TRIAL_END = '2026-04-22 00:00:00';

    private Environment $twig;

    private TranslatorInterface $translator;

    protected static function createKernel(array $options = []): SaasTestKernel
    {
        $env = $options['environment'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? $_SERVER['SOLIDINVOICE_ENV'] ?? 'test';
        $debug = $options['debug'] ?? (bool) ($_ENV['SOLIDINVOICE_DEBUG'] ?? $_SERVER['SOLIDINVOICE_DEBUG'] ?? true);

        return new SaasTestKernel($env, $debug);
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

    public function testAddFirstClientHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(AddFirstClientStep::class)));
    }

    public function testAddFirstClientTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(AddFirstClientStep::class)));
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

    public function testCustomizeLogoHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(CustomizeLogoStep::class)));
    }

    public function testCustomizeLogoTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(CustomizeLogoStep::class)));
    }

    public function testRecurringBillingHtmlSnapshot(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderHtml(self::getContainer()->get(RecurringBillingStep::class)));
    }

    public function testRecurringBillingTextSnapshot(): void
    {
        $this->assertMatchesTextSnapshot($this->renderText(self::getContainer()->get(RecurringBillingStep::class)));
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

    private function renderHtml(OnboardingEmailStepInterface $step): string
    {
        $email = $step->createEmail($this->context());

        return $this->twig->render($email->getHtmlTemplate(), $email->getContext());
    }

    private function renderText(OnboardingEmailStepInterface $step): string
    {
        $email = $step->createEmail($this->context());

        return $this->twig->render($email->getTextTemplate(), $email->getContext());
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
        );
    }

    private function upgradeOfferStep(): UpgradeOfferStep
    {
        return new UpgradeOfferStep($this->translator, 'SOLID30');
    }

    private function trialAboutToEndStep(): TrialAboutToEndStep
    {
        return new TrialAboutToEndStep(
            $this->translator,
            new MockClock(self::FROZEN_NOW),
            self::getContainer()->get(ClientRepository::class),
            self::getContainer()->get(InvoiceRepository::class),
            'SOLID30',
        );
    }
}
