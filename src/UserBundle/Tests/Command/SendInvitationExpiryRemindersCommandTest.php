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
use SolidInvoice\UserBundle\Command\SendInvitationExpiryRemindersCommand;
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
use Zenstruck\Mailer\Test\InteractsWithMailer;
use function rewind;
use function str_replace;
use function stream_get_contents;

#[Group('functional')]
#[CoversClass(SendInvitationExpiryRemindersCommand::class)]
final class SendInvitationExpiryRemindersCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use InteractsWithMailer;
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

    public function testRemindsInvitationsAboutToExpire(): void
    {
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $inviter = $executor->getReferenceRepository()->getReference('user2', User::class);

        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $company = $registry->getRepository(Company::class)->find($this->company->getId());

        // Expiring within the 2-day window (created 6 days ago, expires in ~1 day).
        CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS - 1));
        $expiringSoon = new UserInvitation();
        self::assertInstanceOf(Company::class, $company);
        $expiringSoon->setEmail('soon@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        CarbonImmutable::setTestNow();
        $this->repository->save($expiringSoon);

        // Freshly created, expires in the full validity window (outside the reminder window).
        $notDue = new UserInvitation();
        $notDue->setEmail('later@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($notDue);

        // Expiring soon, but a reminder was already sent.
        CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS - 1));
        $alreadyReminded = new UserInvitation();
        $alreadyReminded->setEmail('reminded@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending)
            ->markReminderSent();
        CarbonImmutable::setTestNow();
        $this->repository->save($alreadyReminded);

        // Already past its expiry date: no reminder should be sent.
        CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS + 2));
        $alreadyExpired = new UserInvitation();
        $alreadyExpired->setEmail('expired@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        CarbonImmutable::setTestNow();
        $this->repository->save($alreadyExpired);

        $soonId = $expiringSoon->getId();
        $notDueId = $notDue->getId();

        $output = $this->runCommand();

        self::assertStringContainsString('Sent 1 invitation expiry reminder(s).', $output);

        // Exactly one reminder, addressed to the invitation expiring within the window.
        $this->mailer()->sentEmails()->whereTo('soon@example.com')->assertCount(1);
        $this->mailer()->sentEmails()->whereTo('later@example.com')->assertCount(0);
        $this->mailer()->sentEmails()->whereTo('reminded@example.com')->assertCount(0);
        $this->mailer()->sentEmails()->whereTo('expired@example.com')->assertCount(0);

        $registry = self::getContainer()->get('doctrine');
        $registry->getManager()->clear();
        $repository = $registry->getRepository(UserInvitation::class);

        self::assertNotNull($repository->find($soonId)?->getReminderSentAt());
        self::assertNull($repository->find($notDueId)?->getReminderSentAt());
    }

    private function runCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('solidinvoice:invitations:send-expiry-reminders');

        /** @var SendInvitationExpiryRemindersCommand $command */
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
