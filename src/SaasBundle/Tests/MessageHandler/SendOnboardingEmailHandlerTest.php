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

namespace SolidInvoice\SaasBundle\Tests\MessageHandler;

use Carbon\CarbonImmutable;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\MessageHandler\SendOnboardingEmailHandler;
use SolidInvoice\SaasBundle\Onboarding\OnboardingStepRegistry;
use SolidInvoice\SaasBundle\Onboarding\Step\WelcomeStep;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Model\UserInterface;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(SendOnboardingEmailHandler::class)]
final class SendOnboardingEmailHandlerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * @var list<RawMessage>
     */
    private array $sent = [];

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    public function testAVerifiedUserGetsTheEmail(): void
    {
        $this->handle(verified: true);

        self::assertCount(1, $this->sent);
    }

    /**
     * Mail to an unconfirmed address is what bounces.
     */
    public function testAnUnverifiedUserGetsNothing(): void
    {
        $this->handle(verified: false);

        self::assertCount(0, $this->sent);
    }

    private function handle(bool $verified): void
    {
        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => $verified]);

        $this->createTrial($user);

        $handler = new SendOnboardingEmailHandler(
            self::getContainer()->get('doctrine'),
            new OnboardingStepRegistry([new WelcomeStep(self::getContainer()->get(TranslatorInterface::class))]),
            self::getContainer()->get(CompanySelector::class),
            $this->mailer(),
            self::getContainer()->get(SystemConfig::class),
            new NullLogger(),
            $this->gate(),
        );

        $handler(new SendOnboardingEmailMessage($user->getId(), 'welcome'));
    }

    private function createTrial(User $user): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setDescription('Pro');
        $plan->setPlanId('pro-handler-test');
        $plan->setPrice(1900);

        $subscription = new Subscription();
        $subscription->setSubscriber($this->company);
        $subscription->setPlan($plan);
        $subscription->setStatus(SubscriptionStatus::TRIAL);
        $subscription->setStartDate(CarbonImmutable::parse('2026-01-01'));
        $subscription->setEndDate(CarbonImmutable::parse('2026-01-15'));

        $trial = new Trial();
        $trial->setUser($user);
        $trial->setSubscription($subscription);

        $entityManager->persist($plan);
        $entityManager->persist($subscription);
        $entityManager->persist($trial);
        $entityManager->flush();
    }

    private function mailer(): MailerInterface
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(function (RawMessage $message): void {
            $this->sent[] = $message;
        });

        return $mailer;
    }

    /**
     * The real gate also reads the enabled flag; the stand-in only needs the user.
     */
    private function gate(): EmailVerificationGateInterface
    {
        return new class() implements EmailVerificationGateInterface {
            public function isGated(?UserInterface $user = null): bool
            {
                return $user instanceof UserInterface && ! $user->isVerified();
            }

            public function reason(string $action): string
            {
                return '';
            }
        };
    }
}
