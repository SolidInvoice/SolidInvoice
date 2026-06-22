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

namespace SolidInvoice\UserBundle\Tests\Repository;

use Carbon\CarbonImmutable;
use Doctrine\ORM\QueryBuilder;
use Faker\Generator;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class UserInvitationRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private Generator $faker;

    private UserInvitationRepository $repository;

    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');
        $this->repository = $registry->getRepository(UserInvitation::class);
        $this->faker = $this->getFaker();

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = self::getContainer()->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseToolCollection->get();
    }

    public function testCountPendingInvitations(): void
    {
        self::assertSame(0, $this->repository->countPendingInvitations());

        // Load user fixtures to have an inviter
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $inviter = $executor->getReferenceRepository()->getReference('user2', User::class);

        // The fixtures executor clears the EntityManager, so re-fetch the
        // company as a managed entity before associating it with new records.
        $company = self::getContainer()->get('doctrine')->getRepository(Company::class)->find($this->company->getId());

        // Create a pending invitation
        $invitation = new UserInvitation();
        $invitation->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($invitation);

        self::assertSame(1, $this->repository->countPendingInvitations());

        // Create another pending invitation
        $invitation2 = new UserInvitation();
        $invitation2->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($invitation2);

        self::assertSame(2, $this->repository->countPendingInvitations());
    }

    public function testGetGridQuery(): void
    {
        $queryBuilder = $this->repository->getGridQuery();
        self::assertInstanceOf(QueryBuilder::class, $queryBuilder);
        $alias = $queryBuilder->getRootAliases()[0];
        self::assertCount(1, $queryBuilder->getDQLPart('select'));
    }

    public function testMarkExpiredFlagsOnlyExpiredInvitations(): void
    {
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $inviter = $executor->getReferenceRepository()->getReference('user2', User::class);

        $registry = self::getContainer()->get('doctrine');
        $company = $registry->getRepository(Company::class)->find($this->company->getId());

        // An invitation created in the past, so its validity window has elapsed.
        CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS + 1));
        $expired = new UserInvitation();
        $expired->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        CarbonImmutable::setTestNow();
        $this->repository->save($expired);

        // A freshly created invitation that is still valid.
        $valid = new UserInvitation();
        $valid->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($valid);

        $expiredId = $expired->getId();
        $validId = $valid->getId();

        $updated = $this->repository->markExpired(CarbonImmutable::now());

        self::assertSame(1, $updated);

        // A bulk DQL UPDATE bypasses the unit of work, so clear the identity map
        // to force the following lookups to hit the database.
        $registry->getManager()->clear();
        self::assertInstanceOf(Ulid::class, $expiredId);

        // The expired invitation is retained, only its status changes.
        self::assertSame(InvitationStatus::Expired, $this->repository->find($expiredId)?->getStatus());
        self::assertInstanceOf(Ulid::class, $validId);
        self::assertSame(InvitationStatus::Pending, $this->repository->find($validId)?->getStatus());
    }

    public function testCountPendingScopesByCompany(): void
    {
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $inviter = $executor->getReferenceRepository()->getReference('user2', User::class);

        $registry = self::getContainer()->get('doctrine');
        $company = $registry->getRepository(Company::class)->find($this->company->getId());

        self::assertSame(0, $this->repository->countPending($company));

        $pending = new UserInvitation();
        $pending->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($pending);

        // A same-company invitation in a non-pending status must not be counted.
        $expired = new UserInvitation();
        $expired->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Expired);
        $this->repository->save($expired);

        self::assertSame(1, $this->repository->countPending($company));
    }

    public function testDeleteInvitationsOnlyRemovesCurrentCompanyInvitations(): void
    {
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $inviter = $executor->getReferenceRepository()->getReference('user2', User::class);

        $registry = self::getContainer()->get('doctrine');
        $manager = $registry->getManager();
        $company = $registry->getRepository(Company::class)->find($this->company->getId());

        // An invitation belonging to another company that must never be deleted
        // through the current company's context.
        $otherCompany = new Company();
        $otherCompany->setName('Other Company');
        $otherCompany->currency = 'USD';

        $manager->persist($otherCompany);

        $own = new UserInvitation();
        $own->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($own);

        $foreign = new UserInvitation();
        $foreign->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($otherCompany)
            ->setStatus(InvitationStatus::Pending);
        $this->repository->save($foreign);

        $ownId = $own->getId();
        $foreignId = $foreign->getId();

        // The company filter (enabled for the current company) must scope deletion.
        $deleted = $this->repository->deleteInvitations([(string) $ownId, (string) $foreignId]);

        self::assertSame(1, $deleted);

        $manager->clear();
        self::assertInstanceOf(Ulid::class, $ownId);
        self::assertNull($this->repository->find($ownId));

        // The other company's invitation survives — verified directly against the
        // database (bypassing ORM filters) so the assertion is unambiguous.
        $remaining = (int) $registry->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ' . UserInvitation::TABLE_NAME)
            ->fetchOne();
        self::assertSame(1, $remaining);
    }
}
