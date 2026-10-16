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
use Doctrine\Persistence\ManagerRegistry;
use Override;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Message\SendTrialDowngradedEmailMessage;
use SolidInvoice\SaasBundle\MessageHandler\SendTrialDowngradedEmailHandler;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_filter;
use function array_values;

/**
 * Verifies that invoking the handler with a persisted subscription + trial
 * sends exactly one email, addressed to the trial owner, that spells out what
 * the Free plan includes.
 */
#[Group('functional')]
final class SendTrialDowngradedEmailHandlerTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use MailerAssertionsTrait;

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    public function testSendsEmailToTheTrialOwner(): void
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

        $free = new Plan()->setName('Free')->setPlanId('0')->setPrice(0)->setActive(true)->setDefault(true);
        $em->persist($free);

        $user = UserFactory::createOne(['companies' => [$company]]);
        self::assertInstanceOf(User::class, $user);

        $subscription = new Subscription()
            ->setSubscriber($company)
            ->setPlan($free)
            ->setStatus(SubscriptionStatus::ACTIVE)
            ->setStartDate(CarbonImmutable::now())
            ->setEndDate(CarbonImmutable::now()->addYears(100));
        $em->persist($subscription);

        $em->persist(Trial::create($user, $subscription));
        $em->flush();

        $container = self::getContainer();
        $handler = new SendTrialDowngradedEmailHandler(
            $container->get(ManagerRegistry::class),
            $container->get(CompanySelector::class),
            $container->get(MailerInterface::class),
            $container->get(SystemConfig::class),
            $container->get(TranslatorInterface::class),
            new NullLogger(),
        );
        ($handler)(new SendTrialDowngradedEmailMessage($subscription->getId()));

        // The app's mailer is wired to the default Messenger bus (see
        // config/packages/mailer.php — no `message_bus: false` override), so
        // Mailer::send() always fires a "queued" MessageEvent up front, in
        // addition to the "sent" one recorded once the (synchronous, since
        // SendEmailMessage isn't routed to an async transport) handler
        // actually hands the message to the transport. Assert on the "sent"
        // side to verify the handler delivered exactly one real email.
        self::assertEmailCount(1);

        $sentEvents = array_values(array_filter(
            self::getMailerEvents(),
            static fn (MessageEvent $event): bool => ! $event->isQueued(),
        ));
        self::assertCount(1, $sentEvents);

        $message = $sentEvents[0]->getMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', (string) $user->getEmail());
        self::assertStringContainsString('Up to 3 active clients', (string) $message->getHtmlBody());
        self::assertStringContainsString('Unlimited invoices', (string) $message->getTextBody());
    }
}
