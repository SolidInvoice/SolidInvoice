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
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Command\DowngradeExpiredTrialsCommand;
use SolidInvoice\SaasBundle\Message\SendTrialDowngradedEmailMessage;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the hourly downgrade-expired-trials command flips an expired trial
 * subscription to the free plan + ACTIVE and queues exactly one notification
 * email, leaves a not-yet-expired trial untouched, and that --dry-run reports
 * without changing any state or queuing any message.
 */
#[Group('functional')]
final class DowngradeExpiredTrialsCommandTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private function seedFreePlan(EntityManagerInterface $em): Plan
    {
        $free = new Plan()->setName('Free')->setPlanId('0')->setPrice(0)->setActive(true)->setDefault(true);
        $em->persist($free);

        return $free;
    }

    private function seedTrial(EntityManagerInterface $em, Plan $plan, string $endDate, ?string $subscriptionId = null): Subscription
    {
        $user = UserFactory::createOne(['companies' => [$this->company]]);
        self::assertInstanceOf(User::class, $user);

        $subscription = new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($plan)
            ->setStatus(SubscriptionStatus::TRIAL)
            ->setStartDate(CarbonImmutable::now()->subMonth())
            ->setEndDate(CarbonImmutable::parse($endDate))
            ->setSubscriptionId($subscriptionId);
        $em->persist($subscription);
        $em->persist(Trial::create($user, $subscription));

        return $subscription;
    }

    /**
     * @param array<string, bool> $options
     */
    private function executeDowngradeCommand(array $options = []): void
    {
        $command = self::getContainer()->get(DowngradeExpiredTrialsCommand::class);

        $input = new ArrayInput($options);
        $input->bind($command->getDefinition());

        $output = new BufferedOutput();
        $command->setIo(new IO($input, $output));

        $tester = new CommandTester($command);
        $tester->execute($options);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    /** @return list<object> */
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

    public function testExpiredTrialIsDowngradedAndEmailQueued(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        // The kernel reboot above leaves $this->company (persisted by the
        // EnsureApplicationInstalled `#[Before]` hook against the previous
        // kernel's EntityManager) unknown to the new EntityManager's unit of
        // work. Re-fetch it so Doctrine treats it as managed rather than a
        // new, uncascaded entity when it's attached to the Subscription.
        $company = $em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        $free = $this->seedFreePlan($em);
        $paid = new Plan()->setName('Pro')->setPlanId('pro')->setPrice(1900)->setActive(true);
        $em->persist($paid);

        $expired = $this->seedTrial($em, $paid, '-1 day');
        $active = $this->seedTrial($em, $paid, '+5 days');
        $em->flush();

        $this->executeDowngradeCommand();

        $em->refresh($expired);
        $em->refresh($active);

        self::assertSame(SubscriptionStatus::ACTIVE, $expired->getStatus());
        self::assertTrue($expired->getPlan()->isFree());

        self::assertSame(SubscriptionStatus::TRIAL, $active->getStatus());
        self::assertFalse($active->getPlan()->isFree());

        $messages = $this->drainAsync();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendTrialDowngradedEmailMessage::class, $messages[0]);
        self::assertTrue($expired->getId()->equals($messages[0]->subscriptionId));
    }

    public function testDryRunChangesNothing(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        // See the comment in testExpiredTrialIsDowngradedAndEmailQueued() above.
        $company = $em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        $this->seedFreePlan($em);
        $paid = new Plan()->setName('Pro')->setPlanId('pro')->setPrice(1900)->setActive(true);
        $em->persist($paid);
        $expired = $this->seedTrial($em, $paid, '-1 day');
        $em->flush();

        $this->executeDowngradeCommand(['--dry-run' => true]);

        $em->refresh($expired);
        self::assertSame(SubscriptionStatus::TRIAL, $expired->getStatus());
        self::assertFalse($expired->getPlan()->isFree());
        self::assertCount(0, $this->drainAsync());
    }

    public function testExternallyBilledExpiredTrialIsSkipped(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        // See the comment in testExpiredTrialIsDowngradedAndEmailQueued() above.
        $company = $em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        $this->seedFreePlan($em);
        $paid = new Plan()->setName('Pro')->setPlanId('pro')->setPrice(1900)->setActive(true);
        $em->persist($paid);

        // Provider-backed trial (a card on file): must never be auto-downgraded
        // locally, since that would leave the customer being billed externally
        // while sitting on the Free plan.
        $externallyBilled = $this->seedTrial($em, $paid, '-1 day', 'ext_test_123');
        $em->flush();

        $this->executeDowngradeCommand();

        $em->refresh($externallyBilled);
        self::assertSame(SubscriptionStatus::TRIAL, $externallyBilled->getStatus());
        self::assertFalse($externallyBilled->getPlan()->isFree());

        self::assertCount(0, $this->drainAsync());
    }

    /**
     * Regression test for a DQL operator-precedence bug: the `andWhere("s.subscriptionId
     * IS NULL OR s.subscriptionId = ''")` clause in {@see SubscriptionRepository::findExpiredTrials()}
     * must group the OR in parentheses. Doctrine's Andx joins andWhere() parts with a bare
     * " AND " and does not parenthesize them, so without the grouping the generated WHERE
     * becomes `(s.status = :status AND s.endDate <= :now AND s.subscriptionId IS NULL) OR
     * s.subscriptionId = ''`, which would match ANY subscription with an empty-string
     * subscriptionId regardless of status or endDate.
     */
    public function testActiveSubscriptionWithEmptySubscriptionIdIsNotDowngraded(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $em = self::getContainer()->get(EntityManagerInterface::class);

        // See the comment in testExpiredTrialIsDowngradedAndEmailQueued() above.
        $company = $em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        $this->seedFreePlan($em);
        $paid = new Plan()->setName('Pro')->setPlanId('pro')->setPrice(1900)->setActive(true);
        $em->persist($paid);

        $subscription = new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($paid)
            ->setStatus(SubscriptionStatus::ACTIVE)
            ->setSubscriptionId('')
            ->setStartDate(CarbonImmutable::now()->subMonth())
            ->setEndDate(CarbonImmutable::parse('-1 day'));
        $em->persist($subscription);
        $em->flush();

        $this->executeDowngradeCommand();

        $em->refresh($subscription);
        self::assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
        self::assertFalse($subscription->getPlan()->isFree());

        self::assertCount(0, $this->drainAsync());
    }
}
