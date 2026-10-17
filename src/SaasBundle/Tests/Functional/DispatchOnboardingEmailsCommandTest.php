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
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Command\DispatchOnboardingEmailsCommand;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\Step\FreeFeedbackStep;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
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
        self::ensureKernelShutdown();
        self::createClient()->disableReboot();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        // The reboot above leaves $this->company unknown to the new EntityManager.
        $company = $em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        $free = new Plan()->setName('Free')->setPlanId('0')->setPrice(0)->setActive(true);
        $em->persist($free);

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

    private function onFree(EntityManagerInterface $em, Plan $free, int $movedDaysAgo): User
    {
        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);

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
