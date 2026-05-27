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

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Command\MarkOverdueInvoicesCommand;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
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

#[CoversClass(MarkOverdueInvoicesCommand::class)]
final class MarkOverdueInvoicesCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use TesterTrait;

    public function testCommandDispatchesMessageForOverdueInvoices(): void
    {
        $company1 = CompanyFactory::createOne();
        $company2 = CompanyFactory::createOne();

        // Create overdue invoices from different companies
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::parse('2 days ago'),
            'company' => $company1,
            'client' => ClientFactory::createOne(['company' => $company1]),
        ]);

        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company2,
            'client' => ClientFactory::createOne(['company' => $company2]),
        ]);

        // Create non-overdue invoice (should not be processed)
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::tomorrow(),
            'company' => $company1,
            'client' => ClientFactory::createOne(['company' => $company1]),
        ]);

        // Create paid invoice (should not be processed)
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Paid,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company1,
            'client' => ClientFactory::createOne(['company' => $company1]),
        ]);

        $output = $this->runCommand();
        self::assertStringContainsString('Processed 2 overdue invoices', $output);
        self::assertStringContainsString('Errors: 0', $output);
    }

    public function testCommandHandlesEmptyResult(): void
    {
        // No overdue invoices
        $company = CompanyFactory::createOne();

        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::tomorrow(),
            'company' => $company,
            'client' => ClientFactory::createOne(['company' => $company]),
        ]);

        $output = $this->runCommand();

        self::assertStringContainsString('Processed 0 overdue invoices', $output);
        self::assertStringContainsString('Errors: 0', $output);
    }

    public function testCommandProcessesInvoicesFromMultipleCompanies(): void
    {
        $company1 = CompanyFactory::createOne();
        $company2 = CompanyFactory::createOne();
        $company3 = CompanyFactory::createOne();

        // Create one overdue invoice per company
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company1,
            'client' => ClientFactory::createOne(['company' => $company1]),
        ]);

        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company2,
            'client' => ClientFactory::createOne(['company' => $company2]),
        ]);

        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company3,
            'client' => ClientFactory::createOne(['company' => $company3]),
        ]);

        $output = $this->runCommand();

        self::assertStringContainsString('Processed 3 overdue invoices', $output);
    }

    /**
     * Regression test for https://github.com/SolidInvoice/SolidInvoice/issues/2380
     *
     * When invoice lines are lazy-loaded into the Doctrine identity map during a
     * workflow transition flush (e.g. by a postUpdate listener), a subsequent
     * detach($invoice) left the lines managed but pointing to the now-detached
     * invoice entity. The next flush then threw ORMInvalidArgumentException
     * ("A new entity was found through Line#invoice"). The fix is to call
     * clear() instead of detach() to purge the entire identity map between
     * iterations, preventing stale association references.
     */
    public function testCommandHandlesMultipleOverdueInvoicesWithLines(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company]);

        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::parse('2 days ago'),
            'company' => $company,
            'client' => $client,
            'lines' => [
                new Line()->setDescription('Service A')->setQty(1)->setPrice(5000),
                new Line()->setDescription('Service B')->setQty(2)->setPrice(2500),
            ],
        ]);

        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company,
            'client' => $client,
            'lines' => [
                new Line()->setDescription('Service C')->setQty(1)->setPrice(7500),
            ],
        ]);

        $output = $this->runCommand();

        self::assertStringContainsString('Processed 2 overdue invoices', $output);
        self::assertStringContainsString('Errors: 0', $output);
    }

    private function runCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('solidinvoice:invoices:mark-overdue');

        /** @var MarkOverdueInvoicesCommand $command */
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
