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

namespace SolidInvoice\CoreBundle\Tests\Doctrine\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Doctrine\Listener\CompanyListener;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\CoreBundle\Doctrine\Listener\CompanyListener
 */
final class CompanyListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAssignsCompanyFromSelectorWhenEntityHasNoneSet(): void
    {
        $sessionCompany = new Company();
        $this->setCompanyId($sessionCompany, $sessionCompanyId = new Ulid());

        $entity = new class() {
            use CompanyAware;
        };

        $listener = new CompanyListener($this->companySelectorWith($sessionCompanyId));

        $eventArgs = $this->prePersistArgs($entity, $sessionCompany, hasAssociation: true);

        $listener->prePersist($eventArgs);

        self::assertSame($sessionCompany, $entity->getCompany());
    }

    public function testDoesNotOverwriteACompanyAlreadySetOnTheEntity(): void
    {
        // Locks in the OAuth/MCP fix: when an upstream caller (eg the consent
        // flow) explicitly sets the company on the entity, the prePersist
        // listener must respect that choice — not silently rewrite it to the
        // session company.
        $sessionCompany = new Company();
        $this->setCompanyId($sessionCompany, $sessionCompanyId = new Ulid());

        $explicitCompany = new Company();
        $this->setCompanyId($explicitCompany, new Ulid());

        $entity = new class() {
            use CompanyAware;
        };
        $entity->setCompany($explicitCompany);

        $listener = new CompanyListener($this->companySelectorWith($sessionCompanyId));

        $eventArgs = $this->prePersistArgs($entity, $sessionCompany, hasAssociation: true, expectRepositoryCall: false);

        $listener->prePersist($eventArgs);

        self::assertSame($explicitCompany, $entity->getCompany());
    }

    public function testNoOpWhenEntityHasNoCompanyAssociation(): void
    {
        $entity = new \stdClass();

        $listener = new CompanyListener($this->companySelectorWith(null));

        $eventArgs = $this->prePersistArgs($entity, null, hasAssociation: false, expectRepositoryCall: false);

        $listener->prePersist($eventArgs);

        // Mock expectations on the EM/repository assert behaviour; assertTrue
        // keeps PHPUnit happy when there's nothing else to inspect.
        self::assertTrue(true);
    }

    public function testNoOpWhenSelectorHasNoCompany(): void
    {
        $entity = new class() {
            use CompanyAware;
        };

        $listener = new CompanyListener($this->companySelectorWith(null));

        $eventArgs = $this->prePersistArgs($entity, null, hasAssociation: true, expectRepositoryCall: false);

        $listener->prePersist($eventArgs);

        $reflection = new ReflectionProperty($entity, 'company');
        self::assertFalse($reflection->isInitialized($entity));
    }

    private function companySelectorWith(?Ulid $companyId): CompanySelector
    {
        $selector = new CompanySelector(M::mock(ManagerRegistry::class));

        if ($companyId !== null) {
            $reflection = new ReflectionProperty(CompanySelector::class, 'companyId');
            $reflection->setValue($selector, $companyId);
        }

        return $selector;
    }

    private function prePersistArgs(
        object $entity,
        ?Company $repositoryReturn,
        bool $hasAssociation,
        bool $expectRepositoryCall = true,
    ): PrePersistEventArgs {
        $metadata = M::mock(ClassMetadata::class);
        $metadata
            ->shouldReceive('hasAssociation')
            ->with('company')
            ->andReturn($hasAssociation);

        if ($hasAssociation) {
            $reflection = new ReflectionProperty($entity, 'company');
            $metadata
                ->shouldReceive('getReflectionProperty')
                ->with('company')
                ->zeroOrMoreTimes()
                ->andReturn($reflection);
        }

        $repository = M::mock(EntityRepository::class);
        if ($expectRepositoryCall) {
            $repository
                ->shouldReceive('find')
                ->once()
                ->with(M::type(Ulid::class))
                ->andReturn($repositoryReturn);
        } else {
            $repository->shouldNotReceive('find');
        }

        $em = M::mock(EntityManagerInterface::class);
        $em
            ->shouldReceive('getClassMetadata')
            ->with($entity::class)
            ->andReturn($metadata);
        $em
            ->shouldReceive('getRepository')
            ->with(Company::class)
            ->zeroOrMoreTimes()
            ->andReturn($repository);

        return new PrePersistEventArgs($entity, $em);
    }

    private function setCompanyId(Company $company, Ulid $id): void
    {
        $reflection = new ReflectionProperty(Company::class, 'id');
        $reflection->setValue($company, $id);
    }
}
