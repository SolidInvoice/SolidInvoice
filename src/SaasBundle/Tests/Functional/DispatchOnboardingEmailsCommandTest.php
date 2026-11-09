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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Command\DispatchOnboardingEmailsCommand;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\Step\FreeFeedbackStep;
use SolidInvoice\SaasBundle\Onboarding\Step\WelcomeStep;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

#[Group('functional')]
final class DispatchOnboardingEmailsCommandTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    public function testAsksAccountsAMonthIntoFreeForFeedbackOnce(): void
    {
        $em = $this->boot();
        $free = $this->freePlan($em);

        $due = $this->onFree($em, $free, movedDaysAgo: 31);
        $this->onFree($em, $free, movedDaysAgo: 10);
        $this->onFree($em, $free, movedDaysAgo: 70);
        $em->flush();

        $this->dispatchOnce();
        $this->dispatchOnce();

        $messages = $this->drainAsync();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendOnboardingEmailMessage::class, $messages[0]);
        self::assertSame(FreeFeedbackStep::key(), $messages[0]->stepKey);
        self::assertTrue($due->getId()->equals($messages[0]->userId));
    }

    /**
     * A Free-default signup has no Trial row. It must still get the sequence,
     * addressed to the company's user.
     */
    public function testAFreeSignupWithoutATrialRowGetsTheWelcome(): void
    {
        $em = $this->boot();
        $free = $this->freePlan($em);

        [$user, $subscription] = $this->signedUpOnFree($em, $free, hoursAgo: 1);
        $em->flush();

        $this->dispatchOnce();

        $messages = $this->drainAsync();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendOnboardingEmailMessage::class, $messages[0]);
        self::assertSame(WelcomeStep::key(), $messages[0]->stepKey);
        self::assertTrue($user->getId()->equals($messages[0]->userId));
        self::assertTrue($subscription->getId()->equals($messages[0]->subscriptionId));
    }

    public function testARunningTrialGetsTheWelcome(): void
    {
        $em = $this->boot();
        $user = $this->onTrial($em);
        $em->flush();

        $this->dispatchOnce();

        $messages = $this->drainAsync();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendOnboardingEmailMessage::class, $messages[0]);
        self::assertSame(WelcomeStep::key(), $messages[0]->stepKey);
        self::assertTrue($user->getId()->equals($messages[0]->userId));
    }

    /**
     * A long-standing member of a company that is now on Free is not welcomed:
     * onboarding is for new users.
     */
    public function testAnOldMemberOfAFreeCompanyGetsNothing(): void
    {
        $em = $this->boot();
        $free = $this->freePlan($em);

        [$user] = $this->signedUpOnFree($em, $free, hoursAgo: 24 * 10);
        $user->setCreated(CarbonImmutable::now()->subYear());
        $em->flush();

        $this->dispatchOnce();

        self::assertSame([], $this->drainAsync());
    }

    /**
     * A user on a trial who also has a Free company gets one email a run, for
     * their trial.
     */
    public function testAUserOnTwoCompaniesIsTickedOnce(): void
    {
        $em = $this->boot();
        $free = $this->freePlan($em);
        $user = $this->onTrial($em);

        $company = CompanyFactory::createOne();
        self::assertInstanceOf(Company::class, $company);
        $company->addUser($user);
        $em->persist(new Subscription()
            ->setSubscriber($company)
            ->setPlan($free)
            ->setStatus(SubscriptionStatus::ACTIVE)
            ->setStartDate(CarbonImmutable::now()->subHour())
            ->setEndDate(CarbonImmutable::now()->addYears(100)));
        $em->flush();

        $this->dispatchOnce();

        self::assertCount(1, $this->drainAsync());
    }

    public function testSkipsFreeAccountsPastTheWindowAndLapsedTrials(): void
    {
        $em = $this->boot();
        $free = $this->freePlan($em);

        $this->signedUpOnFree($em, $free, hoursAgo: 61 * 24);

        $pro = new Plan()->setName('Pro')->setPlanId('pro-onboarding-test')->setPrice(1900)->setActive(true);
        $em->persist($pro);

        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);

        $lapsed = new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($pro)
            ->setStatus(SubscriptionStatus::TRIAL)
            ->setStartDate(CarbonImmutable::now()->subDays(15))
            ->setEndDate(CarbonImmutable::now()->subDay());
        $em->persist($lapsed);
        $em->persist(Trial::create($user, $lapsed));
        $em->flush();

        $this->dispatchOnce();

        self::assertSame([], $this->drainAsync());
    }

    private function boot(): EntityManagerInterface
    {
        self::ensureKernelShutdown();
        self::createClient()->disableReboot();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        // The reboot above leaves $this->company unknown to the new EntityManager.
        $company = $em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        return $em;
    }

    private function freePlan(EntityManagerInterface $em): Plan
    {
        $free = new Plan()->setName('Free')->setPlanId('0')->setPrice(0)->setActive(true);
        $em->persist($free);

        return $free;
    }

    /**
     * Earlier steps count as sent, so only the Free feedback check-in is left.
     */
    private function onFree(EntityManagerInterface $em, Plan $free, int $movedDaysAgo): User
    {
        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);
        self::getContainer()->get(UserSettingRepositoryInterface::class)
            ->saveSetting($user, UserSettingType::OnboardingEmailsSent, 'welcome,send_first_invoice');

        $subscription = new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($free)
            ->setStatus(SubscriptionStatus::ACTIVE)
            ->setStartDate(CarbonImmutable::now()->subDays($movedDaysAgo))
            ->setEndDate(CarbonImmutable::now()->addYears(100));
        $em->persist($subscription);
        $em->persist(Trial::create($user, $subscription));

        return $user;
    }

    private function onTrial(EntityManagerInterface $em): User
    {
        $pro = new Plan()->setName('Pro')->setPlanId('pro-running-trial')->setPrice(1900)->setActive(true);
        $em->persist($pro);

        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);

        $subscription = new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($pro)
            ->setStatus(SubscriptionStatus::TRIAL)
            ->setStartDate(CarbonImmutable::now()->subHour())
            ->setEndDate(CarbonImmutable::now()->addDays(14));
        $em->persist($subscription);
        $em->persist(Trial::create($user, $subscription));

        return $user;
    }

    /**
     * @return array{User, Subscription}
     */
    private function signedUpOnFree(EntityManagerInterface $em, Plan $free, int $hoursAgo): array
    {
        $company = CompanyFactory::createOne();
        self::assertInstanceOf(Company::class, $company);

        $user = UserFactory::createOne(['companies' => [$company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);
        // The inverse side is not synced in memory; the job reads it.
        $company->addUser($user);

        $subscription = new Subscription()
            ->setSubscriber($company)
            ->setPlan($free)
            ->setStatus(SubscriptionStatus::ACTIVE)
            ->setStartDate(CarbonImmutable::now()->subHours($hoursAgo))
            ->setEndDate(CarbonImmutable::now()->addYears(100));
        $em->persist($subscription);

        return [$user, $subscription];
    }

    private function dispatchOnce(): void
    {
        $lazyCommand = new Application(self::$kernel)->find('solidinvoice:saas:dispatch-onboarding-emails');
        self::assertInstanceOf(LazyCommand::class, $lazyCommand);
        $command = $lazyCommand->getCommand();
        self::assertInstanceOf(DispatchOnboardingEmailsCommand::class, $command);

        $input = new ArrayInput([]);
        $input->bind($command->getDefinition());

        $command->setIo(new IO($input, new BufferedOutput()));

        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    /**
     * @return list<object>
     */
    private function drainAsync(): array
    {
        $transport = self::getContainer()->get('messenger.transport.async');
        $messages = [];

        foreach ($transport->get() as $envelope) {
            $messages[] = $envelope->getMessage();
            $transport->ack($envelope);
        }

        return $messages;
    }
}
