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

namespace SolidInvoice\InvoiceBundle\Tests\Command;

use const PHP_EOL;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Command\SendInvoiceRemindersCommand;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\Constraint\CommandIsSuccessful;
use Symfony\Component\Console\Tester\TesterTrait;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Zenstruck\Foundry\Test\Factories;
use function rewind;
use function str_replace;
use function stream_get_contents;

#[CoversClass(SendInvoiceRemindersCommand::class)]
final class SendInvoiceRemindersCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use TesterTrait;

    public function testCommandExecutesSuccessfully(): void
    {
        CompanyFactory::createOne();

        $output = $this->runCommand();

        self::assertThat($this->statusCode, new CommandIsSuccessful());
        self::assertStringContainsString('Processing pre-due reminders', $output);
        self::assertStringContainsString('Processing overdue reminders', $output);
    }

    public function testCommandHandlesMultipleCompanies(): void
    {
        CompanyFactory::createOne();
        CompanyFactory::createOne();

        $output = $this->runCommand();

        self::assertThat($this->statusCode, new CommandIsSuccessful());
        self::assertStringContainsString('Processing pre-due reminders', $output);
        self::assertStringContainsString('Processing overdue reminders', $output);
    }

    public function testCommandHandlesNoCompanies(): void
    {
        $output = $this->runCommand();

        self::assertThat($this->statusCode, new CommandIsSuccessful());
        self::assertStringContainsString('Processing pre-due reminders', $output);
        self::assertStringContainsString('Processing overdue reminders', $output);
    }

    /**
     * Regression test for the cron timeout detected by Sentry (SOLIDINVOICE-74).
     *
     * SendInvoiceReminderMessage must be routed to the async transport so the
     * hourly cron command returns in seconds rather than blocking on SMTP for
     * every qualifying invoice. Without async routing the handler runs inline,
     * and a slow or unreachable mail server causes the command to exceed
     * Sentry's cron max_runtime, producing perpetual "timeout check-in" alerts.
     *
     * If this test fails it means the message is handled synchronously: the
     * handler would have created an InvoiceReminder record during the command run.
     */
    public function testReminderMessagesAreDispatchedAsyncNotHandledInline(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // Reminder settings (invoice/reminder/enabled, pre_due_enabled, pre_due_days=3)
        // are seeded automatically by DefaultData when the test company is created in
        // installApplication(), so no manual insert is needed here.

        // Create a pending invoice due in exactly 3 days — matches the default
        // pre_due_days = 3, so getInvoicesNeedingPreDueReminders(3) will return it.
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::now()->addDays(3),
            'users' => [$contact],
        ]);

        $output = $this->runCommand();

        self::assertThat($this->statusCode, new CommandIsSuccessful());
        self::assertStringContainsString('Dispatched 1 pre-due reminder', $output);

        // If SendInvoiceReminderMessage were handled synchronously, the handler
        // would persist an InvoiceReminder row. Zero rows confirms the message
        // was queued in the async transport and the handler did not run inline.
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $reminderCount = $em->getRepository(InvoiceReminder::class)->count([]);
        self::assertSame(0, $reminderCount, 'SendInvoiceReminderMessage must be routed to the async transport, not handled inline during the cron run');

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(SendInvoiceReminderMessage::class, $transport->getSent()[0]->getMessage());
    }

    private function runCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('solidinvoice:invoices:send-reminders');

        /** @var SendInvoiceRemindersCommand $command */
        $command = $lazyCommand->getCommand();
        $this->initOutput([]);
        $this->input = new ArrayInput([]);
        $this->input->setStream(self::createStream([]));

        $command->setIo(new IO($this->input, $this->output));

        $this->statusCode = $command->run($this->input, $this->output);

        Assert::assertThat($this->statusCode, new CommandIsSuccessful());

        rewind($this->output->getStream());

        $display = stream_get_contents($this->output->getStream());
        return str_replace(PHP_EOL, "\n", $display);
    }
}
