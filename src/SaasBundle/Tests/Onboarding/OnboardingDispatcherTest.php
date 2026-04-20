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

use DateTimeImmutable;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\OnboardingDispatcher;
use SolidInvoice\SaasBundle\Onboarding\OnboardingScheduleCalculator;
use SolidInvoice\SaasBundle\Onboarding\OnboardingStepRegistry;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepFirst;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepSecond;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Ulid;

final class OnboardingDispatcherTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testDispatchesFirstStepWhenNoProgressAndTargetReached(): void
    {
        $user = $this->user();
        $subscription = $this->subscription();

        $userSettingRepository = M::mock(UserSettingRepositoryInterface::class);
        $userSettingRepository
            ->shouldReceive('getSetting')
            ->with($user, UserSettingType::OnboardingEmailSequenceLastStep)
            ->andReturnNull();

        $userSettingRepository
            ->shouldReceive('saveSetting')
            ->once()
            ->with($user, UserSettingType::OnboardingEmailSequenceLastStep, 'first');

        $messageBus = M::mock(MessageBusInterface::class);
        $messageBus
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(static function (SendOnboardingEmailMessage $message): bool {
                return $message->stepKey === 'first';
            })
            ->andReturn(new Envelope(new SendOnboardingEmailMessage(new Ulid(), 'first')));

        $dispatcher = new OnboardingDispatcher(
            new OnboardingStepRegistry([new StepFirst(), new StepSecond()]),
            new OnboardingScheduleCalculator(),
            $userSettingRepository,
            $messageBus,
            new MockClock('2025-01-01 00:00:00'),
            new NullLogger(),
        );

        $dispatcher->tick($user, $subscription);
    }

    public function testDispatchesNextStepAfterLast(): void
    {
        $user = $this->user();
        $subscription = $this->subscription();

        $existing = (new UserSetting())->setValue('first');

        $userSettingRepository = M::mock(UserSettingRepositoryInterface::class);
        $userSettingRepository
            ->shouldReceive('getSetting')
            ->andReturn($existing);

        $userSettingRepository
            ->shouldReceive('saveSetting')
            ->once()
            ->with($user, UserSettingType::OnboardingEmailSequenceLastStep, 'second');

        $messageBus = M::mock(MessageBusInterface::class);
        $messageBus
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(static function (SendOnboardingEmailMessage $message): bool {
                return $message->stepKey === 'second';
            })
            ->andReturn(new Envelope(new SendOnboardingEmailMessage(new Ulid(), 'second')));

        $dispatcher = new OnboardingDispatcher(
            new OnboardingStepRegistry([new StepFirst(), new StepSecond()]),
            new OnboardingScheduleCalculator(),
            $userSettingRepository,
            $messageBus,
            new MockClock('2025-01-08 00:00:00'),
            new NullLogger(),
        );

        $dispatcher->tick($user, $subscription);
    }

    public function testNoOpWhenSequenceAlreadyComplete(): void
    {
        $user = $this->user();
        $subscription = $this->subscription();

        $existing = (new UserSetting())->setValue('second');

        $userSettingRepository = M::mock(UserSettingRepositoryInterface::class);
        $userSettingRepository
            ->shouldReceive('getSetting')
            ->andReturn($existing);

        $userSettingRepository->shouldNotReceive('saveSetting');

        $messageBus = M::mock(MessageBusInterface::class);
        $messageBus->shouldNotReceive('dispatch');

        $dispatcher = new OnboardingDispatcher(
            new OnboardingStepRegistry([new StepFirst(), new StepSecond()]),
            new OnboardingScheduleCalculator(),
            $userSettingRepository,
            $messageBus,
            new MockClock('2025-01-08 00:00:00'),
            new NullLogger(),
        );

        $dispatcher->tick($user, $subscription);
    }

    public function testNoDispatchWhenTargetTimeInFuture(): void
    {
        $user = $this->user();
        $subscription = $this->subscription();

        $existing = (new UserSetting())->setValue('first');

        $userSettingRepository = M::mock(UserSettingRepositoryInterface::class);
        $userSettingRepository
            ->shouldReceive('getSetting')
            ->andReturn($existing);

        $userSettingRepository->shouldNotReceive('saveSetting');

        $messageBus = M::mock(MessageBusInterface::class);
        $messageBus->shouldNotReceive('dispatch');

        // Trial 2025-01-01 → 2025-01-08 (7 days). 2 steps → spacing = 3.5 days.
        // Step index 1 target = 2025-01-04 12:00:00. Clock at 2025-01-02 is before target.
        $dispatcher = new OnboardingDispatcher(
            new OnboardingStepRegistry([new StepFirst(), new StepSecond()]),
            new OnboardingScheduleCalculator(),
            $userSettingRepository,
            $messageBus,
            new MockClock('2025-01-02 00:00:00'),
            new NullLogger(),
        );

        $dispatcher->tick($user, $subscription);
    }

    public function testAdvancesSettingBeforeDispatch(): void
    {
        $user = $this->user();
        $subscription = $this->subscription();

        $userSettingRepository = M::mock(UserSettingRepositoryInterface::class);
        $userSettingRepository
            ->shouldReceive('getSetting')
            ->andReturnNull();

        $order = [];

        $userSettingRepository
            ->shouldReceive('saveSetting')
            ->once()
            ->andReturnUsing(static function () use (&$order): void {
                $order[] = 'save';
            });

        $messageBus = M::mock(MessageBusInterface::class);
        $messageBus
            ->shouldReceive('dispatch')
            ->once()
            ->andReturnUsing(static function (SendOnboardingEmailMessage $message) use (&$order): Envelope {
                $order[] = 'dispatch';

                return new Envelope($message);
            });

        $dispatcher = new OnboardingDispatcher(
            new OnboardingStepRegistry([new StepFirst()]),
            new OnboardingScheduleCalculator(),
            $userSettingRepository,
            $messageBus,
            new MockClock('2025-01-01 00:00:00'),
            new NullLogger(),
        );

        $dispatcher->tick($user, $subscription);

        self::assertSame(['save', 'dispatch'], $order);
    }

    private function subscription(): Subscription
    {
        $subscription = new Subscription();
        $subscription->setStartDate(new DateTimeImmutable('2025-01-01 00:00:00'));
        $subscription->setEndDate(new DateTimeImmutable('2025-01-08 00:00:00'));

        return $subscription;
    }

    private function user(): User
    {
        $user = new User();
        $idProperty = new ReflectionProperty($user, 'id');
        $idProperty->setValue($user, new Ulid());

        return $user;
    }
}
