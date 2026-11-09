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

namespace SolidInvoice\QuoteBundle\Tests\Command;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\ConsoleTesterTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\Command\ExpireQuotesCommand;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Message\ExpireQuotes;
use SolidInvoice\QuoteBundle\Message\Handler\ExpireQuotesHandler;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use function array_map;
use function rewind;
use function stream_get_contents;

#[CoversClass(ExpireQuotesCommand::class)]
#[CoversClass(ExpireQuotesHandler::class)]
final class ExpireQuotesCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use ConsoleTesterTrait;

    public function testExpiresPendingQuotesOncePastTheirLastValidDay(): void
    {
        $company1 = CompanyFactory::createOne();
        $company2 = CompanyFactory::createOne();
        $untouched = CompanyFactory::createOne();

        $expired1 = $this->quote($company1, QuoteStatus::Pending, CarbonImmutable::yesterday());
        $expired2 = $this->quote($company1, QuoteStatus::Pending, CarbonImmutable::parse('-30 days'));
        $expired3 = $this->quote($company2, QuoteStatus::Pending, CarbonImmutable::yesterday());

        // Still valid today: it is valid through its date.
        $validToday = $this->quote($company1, QuoteStatus::Pending, CarbonImmutable::today());
        $validLater = $this->quote($untouched, QuoteStatus::Pending, CarbonImmutable::tomorrow());
        $noExpiry = $this->quote($untouched, QuoteStatus::Pending, null);

        // Only a pending quote can expire.
        $others = array_map(
            fn (QuoteStatus $status): Quote => $this->quote($untouched, $status, CarbonImmutable::yesterday()),
            [QuoteStatus::Draft, QuoteStatus::Accepted, QuoteStatus::Declined, QuoteStatus::Cancelled],
        );

        self::assertStringContainsString('Dispatched quote expiry for 2 companies', $this->runExpireCommand());

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $messages = array_map(static fn ($envelope) => $envelope->getMessage(), $transport->getSent());

        self::assertCount(2, $messages);
        self::assertEqualsCanonicalizing(
            [$company1->getId()->toString(), $company2->getId()->toString()],
            array_map(static fn (ExpireQuotes $message): string => $message->companyId->toString(), $messages),
        );

        $handler = self::getContainer()->get(ExpireQuotesHandler::class);
        foreach ($messages as $message) {
            $handler($message);
        }

        self::assertSame(QuoteStatus::Expired, $this->statusOf($expired1));
        self::assertSame(QuoteStatus::Expired, $this->statusOf($expired2));
        self::assertSame(QuoteStatus::Expired, $this->statusOf($expired3));
        self::assertSame(QuoteStatus::Pending, $this->statusOf($validToday));
        self::assertSame(QuoteStatus::Pending, $this->statusOf($validLater));
        self::assertSame(QuoteStatus::Pending, $this->statusOf($noExpiry));

        foreach ($others as $quote) {
            self::assertNotSame(QuoteStatus::Expired, $this->statusOf($quote));
        }
    }

    private function runExpireCommand(): string
    {
        /** @var LazyCommand $command */
        $command = new Application(self::bootKernel())->find('solidinvoice:quotes:expire');
        $this->initOutput([]);
        $this->input = new ArrayInput([]);
        $inner = $command->getCommand();
        self::assertInstanceOf(ExpireQuotesCommand::class, $inner);
        $inner->setIo(new IO($this->input, $this->output));

        self::assertSame(0, $command->run($this->input, $this->output));

        rewind($this->output->getStream());

        return (string) stream_get_contents($this->output->getStream());
    }

    private function quote(Company $company, QuoteStatus $status, ?CarbonImmutable $due): Quote
    {
        return QuoteFactory::createOne([
            'company' => $company,
            'client' => ClientFactory::createOne(['company' => $company]),
            'status' => $status,
            'due' => $due,
        ]);
    }

    private function statusOf(Quote $quote): ?QuoteStatus
    {
        // Read the column directly: the quotes span several companies.
        $status = self::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->createQueryBuilder()
            ->select('status')
            ->from(Quote::TABLE_NAME)
            ->where('id = :id')
            ->setParameter('id', $quote->getId(), UlidType::NAME)
            ->fetchOne();

        return QuoteStatus::tryFrom((string) $status);
    }
}
