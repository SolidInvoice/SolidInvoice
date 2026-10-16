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

namespace SolidInvoice\SaasBundle\Tests\Onboarding;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\OnboardingDispatcher;
use SolidInvoice\SaasBundle\Onboarding\OnboardingEmailStepInterface;
use SolidInvoice\SaasBundle\Onboarding\OnboardingStepRegistry;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepFirst;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepSecond;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StubStep;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(OnboardingDispatcher::class)]
final class OnboardingDispatcherTest extends TestCase
{
    /**
     * @var array<string, string|null>
     */
    private array $settings = [];

    /**
     * @var list<string>
     */
    private array $log = [];

    public function testSendsTheFirstDueStepAndRecordsItBeforeDispatching(): void
    {
        $this->tick(new StepFirst(), new StepSecond());

        self::assertSame(['save:first', 'dispatch:first'], $this->log);
    }

    public function testSendsALaterStepWhoseConditionIsMetBeforeAnEarlierOne(): void
    {
        $this->tick(new StepFirst(due: false), new StepSecond());

        self::assertSame(['save:second', 'dispatch:second'], $this->log);
    }

    public function testSendsEachStepOnlyOnce(): void
    {
        $this->settings[UserSettingType::OnboardingEmailsSent->value] = 'first';

        $this->tick(new StepFirst(), new StepSecond());
        $this->tick(new StepFirst(), new StepSecond());

        self::assertSame(['save:first,second', 'dispatch:second'], $this->log);
    }

    public function testSendsNothingWhileNoStepIsDue(): void
    {
        $this->tick(new StepFirst(due: false), new StepSecond(due: false));

        self::assertSame([], $this->log);
    }

    public function testUsersPartWayThroughTheOldSequenceAreNotSentWhatTheyAlreadyHad(): void
    {
        $this->settings[UserSettingType::OnboardingEmailSequenceLastStep->value] = 'add_first_client';

        $welcome = new class() extends StubStep {
            public static function key(): string
            {
                return 'welcome';
            }

            public static function priority(): int
            {
                return 100;
            }
        };

        $this->tick($welcome, new StepFirst());

        self::assertSame(['save:welcome,add_first_client,first', 'dispatch:first'], $this->log);
    }

    private function tick(OnboardingEmailStepInterface ...$steps): void
    {
        $user = new User();
        new ReflectionProperty($user, 'id')->setValue($user, new Ulid());

        $subscription = new Subscription();
        $subscription->setSubscriber(new Company());
        $subscription->setPlan(new Plan());
        $subscription->setStartDate(CarbonImmutable::parse('2025-01-01'));
        $subscription->setEndDate(CarbonImmutable::parse('2025-01-15'));

        new OnboardingDispatcher(
            new OnboardingStepRegistry($steps),
            $this->settingRepository(),
            $this->messageBus(),
            new MockClock('2025-01-02'),
            new NullLogger(),
        )->tick($user, $subscription);
    }

    private function settingRepository(): UserSettingRepositoryInterface
    {
        $repository = $this->createStub(UserSettingRepositoryInterface::class);
        $repository->method('getSetting')->willReturnCallback(
            fn (User $user, UserSettingType $key): ?UserSetting => isset($this->settings[$key->value])
                ? new UserSetting()->setKey($key)->setValue($this->settings[$key->value])
                : null,
        );
        $repository->method('saveSetting')->willReturnCallback(
            function (User $user, UserSettingType $key, ?string $value): void {
                $this->settings[$key->value] = $value;
                $this->log[] = 'save:' . $value;
            },
        );

        return $repository;
    }

    private function messageBus(): MessageBusInterface
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            function (SendOnboardingEmailMessage $message): Envelope {
                $this->log[] = 'dispatch:' . $message->stepKey;

                return new Envelope($message);
            },
        );

        return $messageBus;
    }
}
