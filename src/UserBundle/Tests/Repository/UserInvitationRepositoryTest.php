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

use Doctrine\ORM\QueryBuilder;
use Faker\Generator;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group functional
 */
final class UserInvitationRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private Generator $faker;

    private UserInvitationRepository $repository;

    protected AbstractDatabaseTool $databaseTool;

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
            ->setStatus(UserInvitation::STATUS_PENDING);
        $this->repository->save($invitation);

        self::assertSame(1, $this->repository->countPendingInvitations());

        // Create another pending invitation
        $invitation2 = new UserInvitation();
        $invitation2->setEmail($this->faker->email)
            ->setInvitedBy($inviter)
            ->setCompany($company)
            ->setStatus(UserInvitation::STATUS_PENDING);
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
}
