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

namespace SolidInvoice\CoreBundle\Tests\Export\Security\Voter;

use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Export\Security\Voter\ExportJobVoter;
use SolidInvoice\UserBundle\Entity\User;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(ExportJobVoter::class)]
final class ExportJobVoterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGrantsAccessWhenUserAndCompanyMatch(): void
    {
        $userId = new Ulid();
        $companyId = new Ulid();

        $voter = $this->makeVoter($companyId);
        $token = $this->tokenForUser($userId);
        $job = $this->job($userId, $companyId);

        self::assertSame(
            ExportJobVoter::ACCESS_GRANTED,
            $voter->vote($token, $job, [ExportJobVoter::DOWNLOAD]),
        );
    }

    public function testDeniesWhenRequestingUserIsDifferent(): void
    {
        $companyId = new Ulid();

        $voter = $this->makeVoter($companyId);
        $token = $this->tokenForUser(new Ulid());
        $job = $this->job(requestedBy: new Ulid(), company: $companyId);

        self::assertSame(
            ExportJobVoter::ACCESS_DENIED,
            $voter->vote($token, $job, [ExportJobVoter::DOWNLOAD]),
        );
    }

    public function testDeniesWhenJobCompanyIsDifferentFromActive(): void
    {
        $userId = new Ulid();

        $voter = $this->makeVoter(activeCompany: new Ulid());
        $token = $this->tokenForUser($userId);
        $job = $this->job(requestedBy: $userId, company: new Ulid());

        self::assertSame(
            ExportJobVoter::ACCESS_DENIED,
            $voter->vote($token, $job, [ExportJobVoter::DOWNLOAD]),
        );
    }

    public function testDeniesWhenNoCompanyIsActive(): void
    {
        $userId = new Ulid();

        $voter = $this->makeVoter(activeCompany: null);
        $token = $this->tokenForUser($userId);
        $job = $this->job(requestedBy: $userId, company: new Ulid());

        self::assertSame(
            ExportJobVoter::ACCESS_DENIED,
            $voter->vote($token, $job, [ExportJobVoter::DOWNLOAD]),
        );
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $voter = $this->makeVoter(new Ulid());
        $token = $this->tokenForUser(new Ulid());
        $job = $this->job(new Ulid(), new Ulid());

        self::assertSame(
            ExportJobVoter::ACCESS_ABSTAIN,
            $voter->vote($token, $job, ['SOMETHING_ELSE']),
        );
    }

    public function testAbstainsOnNonExportJobSubject(): void
    {
        $voter = $this->makeVoter(new Ulid());
        $token = $this->tokenForUser(new Ulid());

        self::assertSame(
            ExportJobVoter::ACCESS_ABSTAIN,
            $voter->vote($token, new stdClass(), [ExportJobVoter::DOWNLOAD]),
        );
    }

    private function makeVoter(?Ulid $activeCompany): ExportJobVoter
    {
        // CompanySelector is final, so we construct a real instance with a dummy
        // registry (only touched by switchCompany/reset which the voter never calls)
        // and force the active company via reflection.
        $selector = new CompanySelector(M::mock(ManagerRegistry::class));

        $companyIdProp = new ReflectionProperty(CompanySelector::class, 'companyId');
        $companyIdProp->setValue($selector, $activeCompany);

        return new ExportJobVoter($selector);
    }

    private function tokenForUser(Ulid $userId): TokenInterface
    {
        $user = M::mock(User::class);
        $user->shouldReceive('getId')->andReturn($userId);

        $token = M::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        return $token;
    }

    private function job(Ulid $requestedBy, Ulid $company): ExportJob
    {
        $companyEntity = M::mock(Company::class);
        $companyEntity->shouldReceive('getId')->andReturn($company);

        $job = new ExportJob($requestedBy, ExportFormat::Json);
        $job->setCompany($companyEntity);

        return $job;
    }
}
