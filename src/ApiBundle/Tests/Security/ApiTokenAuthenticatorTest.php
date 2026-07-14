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

namespace SolidInvoice\ApiBundle\Tests\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Company\HostType;
use SolidInvoice\CoreBundle\Company\ResolvedHost;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Listener\HostRoutingListener;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Ulid;
use function strtoupper;
use function substr;

final class ApiTokenAuthenticatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSupportsWithoutHeader(): void
    {
        self::assertFalse($this->authenticator()->supports(new Request()));
    }

    #[DataProvider('emptyTokenProvider')]
    public function testSupportsRejectsEmptyToken(string $token): void
    {
        self::assertFalse($this->authenticator()->supports($this->requestWithToken($token)));
    }

    public function testSupportsAcceptsNonEmptyToken(): void
    {
        self::assertTrue($this->authenticator()->supports($this->requestWithToken('a-valid-token')));
    }

    #[DataProvider('emptyTokenProvider')]
    public function testAuthenticateRejectsEmptyToken(string $token): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token provided');

        $this->authenticator()->authenticate($this->requestWithToken($token));
    }

    public function testAuthenticateTrimsTokenBeforeLookup(): void
    {
        $userProvider = M::mock(ApiTokenUserProvider::class);
        $userProvider->expects('getUsernameForToken')
            ->with('a-valid-token')
            ->andReturn('user@example.com');

        $passport = $this->authenticator($userProvider)->authenticate($this->requestWithToken('  a-valid-token  '));

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        self::assertSame('user@example.com', $passport->getBadge(UserBadge::class)->getUserIdentifier());
    }

    public function testOnAuthenticationSuccessRejectsTokenWhenCustomDomainCompanyDoesNotMatch(): void
    {
        // Regression test for GHSA-55q8-pvw3-5gfr / GHSA-fxf4-3h4c-rqrc: an API
        // token issued for company A must not grant access to company B just
        // because the request was sent to company B's custom domain (Host header).
        $ownCompany = $this->companyWithId();
        $victimCompany = $this->companyWithId();

        $apiTokenEntity = new ApiToken();
        $apiTokenEntity->setCompany($ownCompany);

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')
            ->once()
            ->with(ApiTokenHistory::class)
            ->andReturn($this->historyRepositoryExpectingAddHistory());
        $registry->shouldNotReceive('getManager');

        $apiTokenRepository = M::mock(ApiTokenRepository::class);
        $apiTokenRepository->shouldReceive('findOneByPlaintext')
            ->once()
            ->with('a-valid-token')
            ->andReturn($apiTokenEntity);

        $authorizationChecker = M::mock(AuthorizationCheckerInterface::class);
        $authorizationChecker->shouldNotReceive('isGranted');

        $companySelector = new CompanySelector($registry);

        $request = $this->requestWithToken('a-valid-token');
        $request->attributes->set(
            HostRoutingListener::REQUEST_ATTR,
            new ResolvedHost(HostType::CustomDomain, 'victim.example', 'https', 443, $victimCompany)
        );

        $authenticator = $this->authenticator(
            registry: $registry,
            apiTokenRepository: $apiTokenRepository,
            companySelector: $companySelector,
            authorizationChecker: $authorizationChecker,
        );

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API token');

        try {
            $authenticator->onAuthenticationSuccess($request, M::mock(TokenInterface::class), 'api');
        } finally {
            self::assertNull($companySelector->getCompany());
        }
    }

    public function testOnAuthenticationSuccessSwitchesCompanyWhenCustomDomainCompanyMatches(): void
    {
        $company = $this->companyWithId();

        $apiTokenEntity = new ApiToken();
        $apiTokenEntity->setCompany($company);

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')
            ->once()
            ->with(ApiTokenHistory::class)
            ->andReturn($this->historyRepositoryExpectingAddHistory());

        $filter = $this->expectSwitchCompanyCalls($registry, $company);

        $apiTokenRepository = M::mock(ApiTokenRepository::class);
        $apiTokenRepository->shouldReceive('findOneByPlaintext')
            ->once()
            ->with('a-valid-token')
            ->andReturn($apiTokenEntity);

        $authorizationChecker = M::mock(AuthorizationCheckerInterface::class);
        $authorizationChecker->shouldReceive('isGranted')->once()->andReturn(true);

        $companySelector = new CompanySelector($registry);

        $request = $this->requestWithToken('a-valid-token');
        $request->attributes->set(
            HostRoutingListener::REQUEST_ATTR,
            new ResolvedHost(HostType::CustomDomain, 'acme.example', 'https', 443, $company)
        );

        $authenticator = $this->authenticator(
            registry: $registry,
            apiTokenRepository: $apiTokenRepository,
            companySelector: $companySelector,
            authorizationChecker: $authorizationChecker,
        );

        $response = $authenticator->onAuthenticationSuccess($request, M::mock(TokenInterface::class), 'api');

        self::assertNull($response);
        self::assertSame($company->getId(), $companySelector->getCompany());
        self::assertSame($company->getId()->toHex(), $filter->getParameter('companyId'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyTokenProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace' => ['   '];
    }

    private function requestWithToken(string $token): Request
    {
        $request = new Request(server: ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'phpunit']);
        $request->headers->set('X-API-TOKEN', $token);

        return $request;
    }

    private function companyWithId(): Company
    {
        $company = new Company();
        new ReflectionClass($company)->getProperty('id')->setValue($company, new Ulid());

        return $company;
    }

    /**
     * @return ApiTokenHistoryRepository&M\MockInterface
     */
    private function historyRepositoryExpectingAddHistory()
    {
        $historyRepository = M::mock(ApiTokenHistoryRepository::class);
        $historyRepository->shouldReceive('addHistory')->once();

        return $historyRepository;
    }

    /**
     * @param ManagerRegistry&M\MockInterface $registry
     */
    private function expectSwitchCompanyCalls($registry, Company $company): SQLFilter
    {
        $filterCollection = M::mock(FilterCollection::class);
        $em = M::mock(EntityManagerInterface::class);
        $connection = M::mock(Connection::class);

        $filter = new class($em) extends SQLFilter {
            /**
             * @param ClassMetadata<object> $targetEntity
             */
            public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
            {
                return '';
            }
        };

        $registry
            ->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $em
            ->shouldReceive('getFilters')
            ->twice()
            ->andReturn($filterCollection);

        $em
            ->shouldReceive('getConnection')
            ->zeroOrMoreTimes()
            ->andReturn($connection);

        $filterCollection
            ->shouldReceive('enable')
            ->once()
            ->with('company')
            ->andReturn($filter);

        $filterCollection
            ->shouldReceive('setFiltersStateDirty')
            ->once()
            ->withNoArgs();

        $connection
            ->shouldReceive('getDatabasePlatform')
            ->zeroOrMoreTimes()
            ->andReturn(new SQLitePlatform());

        $connection
            ->shouldReceive('quote')
            ->once()
            ->with(strtoupper(substr($company->getId()->toHex(), 2)))
            ->andReturn($company->getId()->toHex());

        return $filter;
    }

    /**
     * The constructor pulls in several final, infrastructure-bound services that
     * are not exercised by token extraction, so the instance is built without the
     * constructor and only the collaborators a given test needs are injected.
     */
    private function authenticator(
        ?ApiTokenUserProvider $userProvider = null,
        ?ManagerRegistry $registry = null,
        ?ApiTokenRepository $apiTokenRepository = null,
        ?CompanySelector $companySelector = null,
        ?AuthorizationCheckerInterface $authorizationChecker = null,
    ): ApiTokenAuthenticator {
        $reflection = new ReflectionClass(ApiTokenAuthenticator::class);
        $authenticator = $reflection->newInstanceWithoutConstructor();

        if ($userProvider instanceof ApiTokenUserProvider) {
            $reflection->getProperty('userProvider')->setValue($authenticator, $userProvider);
        }

        if ($registry instanceof ManagerRegistry) {
            $reflection->getProperty('registry')->setValue($authenticator, $registry);
        }

        if ($apiTokenRepository instanceof ApiTokenRepository) {
            $reflection->getProperty('apiTokenRepository')->setValue($authenticator, $apiTokenRepository);
        }

        if ($companySelector instanceof CompanySelector) {
            $reflection->getProperty('companySelector')->setValue($authenticator, $companySelector);
        }

        if ($authorizationChecker instanceof AuthorizationCheckerInterface) {
            $reflection->getProperty('authorizationChecker')->setValue($authenticator, $authorizationChecker);
        }

        return $authenticator;
    }
}
