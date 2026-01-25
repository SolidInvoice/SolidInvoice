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

use PHPUnit\Framework\Assert;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Command\SendInvoiceRemindersCommand;
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

/** @covers \SolidInvoice\InvoiceBundle\Command\SendInvoiceRemindersCommand */
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
        return str_replace(\PHP_EOL, "\n", $display);
    }
}
