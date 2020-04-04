<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\UserBundle\Tests\Repository;

use Doctrine\ORM\QueryBuilder;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\UserBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepositoryTest extends KernelTestCase
{
    use FakerTestTrait;
    use FixturesTrait;

    private $faker;

    /**
     * @var UserRepository
     */
    private $repository;

    protected function setUp()
    {
        parent::setUp();

        $kernel = $this->bootKernel();
        $this->repository = $kernel->getContainer()->get('doctrine')->getRepository(User::class);
        $this->faker = $this->getFaker();
    }

    public function testSave()
    {
        $user = new User();
        $user->setUsername($this->faker->userName)
            ->setEmail($this->faker->email)
            ->setPassword($this->faker->password);

        $this->repository->save($user);

        $this->assertNotNull($user->getId());
        $this->assertCount(1, $this->repository->findAll());
    }

    public function testRefreshUser()
    {
        $executor = $this->loadFixtures([LoadData::class], true);
        $user = $executor->getReferenceRepository()->getReference('user2');
        $newUser = $this->repository->refreshUser($user);
        $this->assertSame($user->getId(), $newUser->getId());
        $this->assertSame($user->getUsername(), $newUser->getUsername());
        $this->assertSame($user->getEmail(), $newUser->getEmail());
    }

    public function testRefreshUserWithInvalidUser()
    {
        $user = new class() implements UserInterface {
            public function getRoles()
            {
            }

            public function getPassword()
            {
            }

            public function getSalt()
            {
            }

            public function getUsername()
            {
            }

            public function eraseCredentials()
            {
            }
        };

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Instances of "%s" are not supported.', get_class($user)));
        $this->repository->refreshUser($user);
    }

    public function testLoadUserByUsername()
    {
        $this->loadFixtures([LoadData::class], true);
        $this->assertInstanceOf(User::class, $this->repository->loadUserByUsername('test2'));
        $this->assertInstanceOf(User::class, $this->repository->loadUserByUsername('test2@test.com'));
    }

    public function testLoadUserByUsernameWithDisabledUser()
    {
        $this->loadFixtures([LoadData::class], true);
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test1" does not exist.');
        $this->repository->loadUserByUsername('test1');
    }

    public function testLoadUserByUsernameWithInvalidUser()
    {
        $username = $this->faker->userName;
        $this->loadFixtures([LoadData::class], true);
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "'.$username.'" does not exist.');
        $this->repository->loadUserByUsername($username);
    }

    public function testGetUserCount()
    {
        $this->assertSame(0, $this->repository->getUserCount());

        $this->loadFixtures([LoadData::class], true);

        $this->assertSame(2, $this->repository->getUserCount());
    }

    public function testSupportsClass()
    {
        $this->assertFalse($this->repository->supportsClass(self::class));
        $this->assertTrue($this->repository->supportsClass(User::class));
    }

    public function testDeleteUsers()
    {
        $executor = $this->loadFixtures([LoadData::class], true);

        $userIds = [$executor->getReferenceRepository()->getReference('user1')->getId(), $executor->getReferenceRepository()->getReference('user2')->getId()];

        $this->assertSame(2, $this->repository->deleteUsers($userIds));
        $this->assertCount(0, $this->repository->findAll());
    }

    public function testClearUserConfirmationToken()
    {
        $executor = $this->loadFixtures([LoadData::class], true);
        /** @var User $user1 */
        $user1 = $executor->getReferenceRepository()->getReference('user1');

        $this->assertNotNull($user1->getConfirmationToken());
        $this->assertNotNull($user1->getPasswordRequestedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user1->getPasswordRequestedAt());

        $this->repository->clearUserConfirmationToken($user1);

        $this->assertNull($user1->getConfirmationToken());
        $this->assertNull($user1->getPasswordRequestedAt());
    }

    public function testGetGridQuery()
    {
        $queryBuilder = $this->repository->getGridQuery();
        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
        $alias = $queryBuilder->getRootAliases()[0];
        $fields = implode(', ', ["$alias.id", "$alias.username", "$alias.email", "$alias.enabled", "$alias.created"]);
        $this->assertCount(1, $queryBuilder->getDQLPart('select'));
        $this->assertSame($fields, (string) $queryBuilder->getDQLPart('select')[0]);
    }
}
