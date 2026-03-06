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

namespace SolidInvoice\CoreBundle\Tests\Action;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Action\SearchSuggestions;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

final class SearchSuggestionsTest extends TestCase
{
    private CompanySelector $companySelector;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->companySelector = new CompanySelector($registry);
    }

    private function setCompany(): void
    {
        $prop = new ReflectionProperty(CompanySelector::class, 'companyId');
        $prop->setValue($this->companySelector, new Ulid());
    }

    private function makeAction(MockObject&ClientRepository $repository): SearchSuggestions
    {
        return new SearchSuggestions($repository, $this->companySelector);
    }

    public function testReturnsEmptyWhenNoCompanyIsSet(): void
    {
        $repository = $this->createMock(ClientRepository::class);
        $repository->expects(self::never())->method('createQueryBuilder');

        $action = $this->makeAction($repository);
        $response = $action(new Request(['qualifier' => 'client', 'q' => 'acme']));

        self::assertSame('[]', $response->getContent());
    }

    public function testReturnsEmptyForUnknownQualifier(): void
    {
        $this->setCompany();

        $repository = $this->createMock(ClientRepository::class);
        $repository->expects(self::never())->method('createQueryBuilder');

        $action = $this->makeAction($repository);
        $response = $action(new Request(['qualifier' => 'unknown', 'q' => 'test']));

        self::assertSame('[]', $response->getContent());
    }

    public function testReturnsEmptyForMissingQualifier(): void
    {
        $this->setCompany();

        $repository = $this->createMock(ClientRepository::class);
        $repository->expects(self::never())->method('createQueryBuilder');

        $action = $this->makeAction($repository);
        $response = $action(new Request(['q' => 'test']));

        self::assertSame('[]', $response->getContent());
    }

    public function testClientQualifierQueriesRepository(): void
    {
        $this->setCompany();
        $names = ['Acme Corp', 'Acme Ltd'];

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleColumnResult')->willReturn($names);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repository = $this->createMock(ClientRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($qb);

        $action = $this->makeAction($repository);
        $response = $action(new Request(['qualifier' => 'client', 'q' => 'acme']));

        $data = json_decode((string) $response->getContent(), true);

        self::assertSame($names, $data);
    }

    public function testClientQualifierWithEmptyPartialReturnsAllMatches(): void
    {
        $this->setCompany();
        $names = ['Alpha Corp', 'Beta Inc'];

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleColumnResult')->willReturn($names);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repository = $this->createMock(ClientRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $action = $this->makeAction($repository);
        $response = $action(new Request(['qualifier' => 'client', 'q' => '']));

        $data = json_decode((string) $response->getContent(), true);

        self::assertSame($names, $data);
    }

    public function testResponseContentTypeIsJson(): void
    {
        $repository = $this->createMock(ClientRepository::class);

        $action = $this->makeAction($repository);
        $response = $action(new Request(['qualifier' => 'client', 'q' => '']));

        self::assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function testStatusQualifierReturnsEmpty(): void
    {
        $this->setCompany();

        $repository = $this->createMock(ClientRepository::class);
        $repository->expects(self::never())->method('createQueryBuilder');

        $action = $this->makeAction($repository);
        $response = $action(new Request(['qualifier' => 'status', 'q' => 'paid']));

        self::assertSame('[]', $response->getContent());
    }

    public function testRepositoryIsQueriedWithPartialAsLikePattern(): void
    {
        $this->setCompany();

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleColumnResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        // The partial 'acme' should be wrapped in % wildcards
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('partial', '%acme%')
            ->willReturnSelf();

        $repository = $this->createMock(ClientRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $action = $this->makeAction($repository);
        $action(new Request(['qualifier' => 'client', 'q' => 'acme']));
    }

    public function testRepositoryIsLimitedToTenResults(): void
    {
        $this->setCompany();

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleColumnResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $qb->expects(self::once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $repository = $this->createMock(ClientRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $action = $this->makeAction($repository);
        $action(new Request(['qualifier' => 'client', 'q' => '']));
    }
}
