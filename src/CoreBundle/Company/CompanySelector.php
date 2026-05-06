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

namespace SolidInvoice\CoreBundle\Company;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Service\ResetInterface;
use function assert;
use function strtoupper;
use function substr;

final class CompanySelector implements CompanySelectorInterface, ResetInterface
{
    private ?Ulid $companyId = null;

    /**
     * @var array{host: string, scheme: string, httpPort: int, httpsPort: int}|null
     */
    private ?array $originalRequestContext = null;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly ?RequestContext $requestContext = null,
    ) {
    }

    public function getCompany(): ?Ulid
    {
        return $this->companyId;
    }

    public function switchCompany(Ulid $companyId): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $isSqlite = $em->getConnection()->getDatabasePlatform() instanceof SqlitePlatform;

        $parameters = $isSqlite ?
            [
                strtoupper(substr($companyId->toHex(), 2)),
                'string',
            ] :
            [
                $companyId,
                UlidType::NAME,
            ];

        $em
            ->getFilters()
            ->enable('company')
            ->setParameter('companyId', ...$parameters);

        $this->companyId = $companyId;

        $this->applyCustomDomain($companyId);
    }

    public function reset(): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $filters = $em->getFilters();

        if ($filters->isEnabled('company')) {
            $filters->disable('company');
        }

        $this->companyId = null;

        $this->restoreRequestContext();
    }

    private function applyCustomDomain(Ulid $companyId): void
    {
        if ($this->requestContext === null) {
            return;
        }

        $company = $this->registry->getRepository(Company::class)->find($companyId);
        $customDomain = $company?->getCustomDomain();

        if ($customDomain === null || $customDomain === '') {
            return;
        }

        if ($this->originalRequestContext === null) {
            $this->originalRequestContext = [
                'host' => $this->requestContext->getHost(),
                'scheme' => $this->requestContext->getScheme(),
                'httpPort' => $this->requestContext->getHttpPort(),
                'httpsPort' => $this->requestContext->getHttpsPort(),
            ];
        }

        $this->requestContext->setHost($customDomain);
        $this->requestContext->setScheme('https');
        $this->requestContext->setHttpsPort(443);
    }

    private function restoreRequestContext(): void
    {
        if ($this->requestContext === null || $this->originalRequestContext === null) {
            return;
        }

        $this->requestContext->setHost($this->originalRequestContext['host']);
        $this->requestContext->setScheme($this->originalRequestContext['scheme']);
        $this->requestContext->setHttpPort($this->originalRequestContext['httpPort']);
        $this->requestContext->setHttpsPort($this->originalRequestContext['httpsPort']);

        $this->originalRequestContext = null;
    }
}
