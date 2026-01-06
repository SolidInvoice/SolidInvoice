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
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @group functional
 */
final class UserRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private Generator $faker;

    private UserRepository $repository;

    protected AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');
        $em = $registry->getManager();
        $this->repository = $registry->getRepository(User::class);
        $this->faker = $this->getFaker();

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = self::getContainer()->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseToolCollection->get();

        // Ensure there are no users set, to make the tests a bit more predictable,
        // since users can be added by api tests
        foreach ($this->repository->findAll() as $user) {
            foreach ($user->getApiTokens() as $token) {
                $em->remove($token);
            }

            foreach ($user->getCompanies() as $company) {
                $user->removeCompany($company);
            }

            $em->flush();

            $em->remove($user);
        }

        $em->flush();
    }

    public function testSave(): void
    {
        $user = new User();
        $user->setEmail($this->faker->email)
            ->setPassword($this->faker->password)
            ->addCompany($this->company)
        ;

        $this->repository->save($user);

        self::assertNotNull($user->getId());
        self::assertCount(1, $this->repository->findAll());
    }

    public function testSaveWithoutAnyCompanyLinkedToUser(): void
    {
        $user = new User();
        $user->setEmail($this->faker->email)
            ->setPassword($this->faker->password)
        ;

        $this->repository->save($user);

        self::assertNotNull($user->getId());
        self::assertCount(0, $this->repository->findAll());
    }

    public function testRefreshUser(): void
    {
        $executor = $this->databaseTool->loadFixtures([LoadData::class], true);
        $user = $executor->getReferenceRepository()->getReference('user2', User::class);
        $newUser = $this->repository->refreshUser($user);
        self::assertInstanceOf(User::class, $newUser);
        self::assertSame($user->getId(), $newUser->getId());
        self::assertSame($user->getEmail(), $newUser->getEmail());
    }

    public function testRefreshUserWithInvalidUser(): void
    {
        $user = new class() implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function getUserIdentifier(): string
            {
                return 'abc';
            }

            public function eraseCredentials(): void
            {
            }
        };

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Instances of "%s" are not supported.', $user::class));
        $this->repository->refreshUser($user);
    }

    public function testLoadUserByIdentifier(): void
    {
        $this->databaseTool->loadFixtures([LoadData::class], true);
        self::assertInstanceOf(User::class, $this->repository->loadUserByIdentifier('test2@test.com'));
    }

    public function testLoadUserByIdentifierWithDisabledUser(): void
    {
        $this->databaseTool->loadFixtures([LoadData::class], true);
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User "test1" does not exist.');
        $this->repository->loadUserByIdentifier('test1');
    }

    public function testLoadUserByIdentifierWithInvalidUser(): void
    {
        $email = $this->faker->email;
        $this->databaseTool->loadFixtures([LoadData::class], true);
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User "' . $email . '" does not exist.');
        $this->repository->loadUserByIdentifier($email);
    }

    public function testGetUserCount(): void
    {
        self::assertSame(0, $this->repository->getUserCount());

        $this->databaseTool->loadFixtures([LoadData::class], true);

        self::assertSame(2, $this->repository->getUserCount());
    }

    public function testGetRecentlyJoinedCount(): void
    {
        self::assertSame(0, $this->repository->getRecentlyJoinedCount());

        // Create a user that joined recently (within 30 days)
        $recentUser = new User();
        $recentUser->setEmail($this->faker->email)
            ->setPassword($this->faker->password)
            ->addCompany($this->company);
        $this->repository->save($recentUser);

        self::assertSame(1, $this->repository->getRecentlyJoinedCount());
        self::assertSame(1, $this->repository->getRecentlyJoinedCount(30));

        // Create a user that joined more than 30 days ago
        $oldUser = new User();
        $oldUser->setEmail($this->faker->email)
            ->setPassword($this->faker->password)
            ->addCompany($this->company);

        $registry = self::getContainer()->get('doctrine');
        $em = $registry->getManager();
        $em->persist($oldUser);
        $em->flush();

        // Use reflection to set the created date to 40 days ago
        $reflection = new \ReflectionClass($oldUser);
        $property = $reflection->getProperty('created');
        $property->setValue($oldUser, new \DateTimeImmutable('-40 days'));
        $em->flush();

        // Should still count only the recent user (within 30 days)
        self::assertSame(1, $this->repository->getRecentlyJoinedCount(30));

        // But if we check for 50 days, both should be counted
        self::assertSame(2, $this->repository->getRecentlyJoinedCount(50));
    }

    public function testSupportsClass(): void
    {
        self::assertFalse($this->repository->supportsClass(self::class));
        self::assertTrue($this->repository->supportsClass(User::class));
    }

    public function testGetGridQuery(): void
    {
        $queryBuilder = $this->repository->getGridQuery();
        self::assertInstanceOf(QueryBuilder::class, $queryBuilder);
        $alias = $queryBuilder->getRootAliases()[0];
        $fields = implode(', ', ["{$alias}.id", "{$alias}.email", "{$alias}.mobile", "{$alias}.enabled", "{$alias}.created", "{$alias}.lastLogin"]);
        self::assertCount(1, $queryBuilder->getDQLPart('select'));
        self::assertSame($fields, (string) $queryBuilder->getDQLPart('select')[0]);
    }
}
