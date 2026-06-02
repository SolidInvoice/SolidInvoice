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

namespace SolidInvoice\InvoiceBundle\Tests\MessageHandler;

use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\InvoiceBundle\MessageHandler\SendInvoiceReminderHandler;
use SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Mailer\MailerInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SendInvoiceReminderHandler skips and logs (without throwing or
 * retrying) when the SaaS `automated_reminders` feature is disabled, and sends
 * normally when the feature is enabled / in self-hosted mode.
 */
#[CoversClass(SendInvoiceReminderHandler::class)]
final class SendInvoiceReminderHandlerFeatureGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testHandlerSkipsAndLogsWhenFeatureDisabled(): void
    {
        $invoice = $this->createPendingInvoice();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $key !== 'automated_reminders');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $logger = new BufferingLogger();

        $handler = $this->buildHandler($featureGate, $mailer, $logger);

        $message = new SendInvoiceReminderMessage(
            $invoice->getId(),
            $this->company->getId(),
            ReminderType::PreDue,
            3,
        );

        // No exception, no throw — handler should swallow + log.
        $handler($message);

        $logs = $logger->cleanLogs();
        $messages = array_map(static fn (array $log): string => $log[1], $logs);

        self::assertContains('Automated reminders feature is disabled for plan, skipping reminder', $messages);
    }

    public function testHandlerProceedsWhenFeatureEnabled(): void
    {
        $invoice = $this->createPendingInvoice();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $logger = new BufferingLogger();

        $handler = $this->buildHandler($featureGate, $mailer, $logger);

        // Ensure reminders are enabled in settings as well
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $reminderEnabled = $systemConfig->get('invoice/reminder/enabled');
        $preDueEnabled = $systemConfig->get('invoice/reminder/pre_due_enabled');

        if ($reminderEnabled !== '1' || $preDueEnabled !== '1') {
            self::markTestSkipped('Default reminder settings not enabled in this fixture');
        }

        $message = new SendInvoiceReminderMessage(
            $invoice->getId(),
            $this->company->getId(),
            ReminderType::PreDue,
            3,
        );

        $handler($message);

        $logs = $logger->cleanLogs();
        $messages = array_map(static fn (array $log): string => $log[1], $logs);

        self::assertNotContains('Automated reminders feature is disabled for plan, skipping reminder', $messages);
    }

    public function testHandlerProceedsInSelfHostedMode(): void
    {
        $invoice = $this->createPendingInvoice();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $logger = new BufferingLogger();

        // NoopFeatureGate always returns true for isEnabled — self-hosted behavior.
        $handler = $this->buildHandler(new NoopFeatureGate(), $mailer, $logger);

        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $reminderEnabled = $systemConfig->get('invoice/reminder/enabled');
        $preDueEnabled = $systemConfig->get('invoice/reminder/pre_due_enabled');

        if ($reminderEnabled !== '1' || $preDueEnabled !== '1') {
            self::markTestSkipped('Default reminder settings not enabled in this fixture');
        }

        $message = new SendInvoiceReminderMessage(
            $invoice->getId(),
            $this->company->getId(),
            ReminderType::PreDue,
            3,
        );

        $handler($message);

        $logs = $logger->cleanLogs();
        $messages = array_map(static fn (array $log): string => $log[1], $logs);

        self::assertNotContains('Automated reminders feature is disabled for plan, skipping reminder', $messages);
    }

    private function buildHandler(
        FeatureGate $featureGate,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): SendInvoiceReminderHandler {
        $container = self::getContainer();

        return new SendInvoiceReminderHandler(
            $container->get(ManagerRegistry::class),
            $container->get(CompanySelector::class),
            $mailer,
            $container->get(NotificationManager::class),
            $container->get(SystemConfig::class),
            $container->get(ClockInterface::class),
            $logger,
            $container->get(InvoiceReminderRepository::class),
            $featureGate,
        );
    }

    private function createPendingInvoice(): Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::now()->modify('+3 days'),
            'users' => [$contact],
        ]);

        return $invoice->_real();
    }
}
