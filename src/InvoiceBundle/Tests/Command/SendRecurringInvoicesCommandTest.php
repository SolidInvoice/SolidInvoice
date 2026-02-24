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

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Command\SendRecurringInvoicesCommand;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\Constraint\CommandIsSuccessful;
use Symfony\Component\Console\Tester\TesterTrait;
use Zenstruck\Foundry\Test\Factories;
use function rewind;
use function str_replace;
use function stream_get_contents;

/** @covers \SolidInvoice\InvoiceBundle\Command\SendRecurringInvoicesCommand */
final class SendRecurringInvoicesCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use TesterTrait;

    public function testCommandExecutesSuccessfully(): void
    {
        $company1 = CompanyFactory::createOne();
        $company2 = CompanyFactory::createOne();

        // Create active recurring invoices
        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'company' => $company1,
        ]);

        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'company' => $company2,
        ]);

        // Create inactive recurring invoice (should not be processed)
        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Complete,
            'company' => $company1,
        ]);

        $this->runCommand();
        self::assertThat($this->statusCode, new CommandIsSuccessful());
    }

    public function testCommandHandlesNoActiveRecurringInvoices(): void
    {
        // No active recurring invoices
        $company = CompanyFactory::createOne();

        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Complete,
            'company' => $company,
        ]);

        $this->runCommand();
        self::assertThat($this->statusCode, new CommandIsSuccessful());
    }

    public function testCommandProcessesInvoicesFromMultipleCompanies(): void
    {
        $company1 = CompanyFactory::createOne();
        $company2 = CompanyFactory::createOne();
        $company3 = CompanyFactory::createOne();

        // Create one active recurring invoice per company
        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'company' => $company1,
        ]);

        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'company' => $company2,
        ]);

        RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'company' => $company3,
        ]);

        $this->runCommand();
        self::assertThat($this->statusCode, new CommandIsSuccessful());
    }

    private function runCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('solidinvoice:recurring:send-invoices');

        /** @var SendRecurringInvoicesCommand $command */
        $command = $lazyCommand->getCommand();
        $this->initOutput([]);
        $this->input = new ArrayInput([]);
        $this->input->setStream(self::createStream([]));
        $command->setIo(new IO($this->input, $this->output));

        $this->statusCode = $command->run($this->input, $this->output);

        rewind($this->output->getStream());

        $display = stream_get_contents($this->output->getStream());
        return str_replace(\PHP_EOL, "\n", $display);
    }
}
