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

namespace SolidInvoice\UserBundle\Tests\Command;

use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Command\MarkExpiredInvitationsCommand;
use SolidInvoice\UserBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\Constraint\CommandIsSuccessful;
use Symfony\Component\Console\Tester\TesterTrait;
use function rewind;
use function str_replace;
use function stream_get_contents;

#[Group('functional')]
#[CoversClass(MarkExpiredInvitationsCommand::class)]
final class MarkExpiredInvitationsCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use TesterTrait;

    private UserInvitationRepository $repository;

    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');
        $this->repository = $registry->getRepository(UserInvitation::class);

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = self::getContainer()->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseToolCollection->get();
    }

    public function testCommandMarksExpiredInvitations(): void
    {
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $inviter = $executor->getReferenceRepository()->getReference('user2', User::class);

        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $company = $registry->getRepository(Company::class)->find($this->company->getId());

        CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS + 1));
        $expired = new UserInvitation();
        self::assertInstanceOf(Company::class, $company);
        $expired->setEmail('expired@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        CarbonImmutable::setTestNow();
        $this->repository->save($expired);

        $valid = new UserInvitation();
        $valid->setEmail('valid@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($valid);

        $expiredId = $expired->getId();
        $validId = $valid->getId();

        $output = $this->runCommand();

        self::assertStringContainsString('Marked 1 invitation(s) as expired.', $output);

        // The command runs a bulk UPDATE, so refresh from the database before asserting.
        $registry = self::getContainer()->get('doctrine');
        $registry->getManager()->clear();
        $repository = $registry->getRepository(UserInvitation::class);

        self::assertSame(InvitationStatus::Expired, $repository->find($expiredId)?->getStatus());
        self::assertSame(InvitationStatus::Pending, $repository->find($validId)?->getStatus());
    }

    private function runCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('solidinvoice:invitations:mark-expired');

        /** @var MarkExpiredInvitationsCommand $command */
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
