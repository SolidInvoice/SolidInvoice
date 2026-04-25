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

use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Uid\Ulid;
use function is_int;
use function is_string;
use function parse_url;
use function rtrim;
use function strtolower;

/**
 * Updates the router request context to match the active company's custom domain (or the
 * configured `SOLIDINVOICE_APPLICATION_URL`) so absolute URLs generated from CLI / Messenger
 * use the correct host.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Company\CompanySelectorRouterDecoratorTest
 */
#[AsDecorator(CompanySelector::class)]
final class CompanySelectorRouterDecorator extends CompanySelector
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RequestContext $requestContext,
        private readonly string $applicationUrl = '',
    ) {
        parent::__construct($registry);
    }

    public function switchCompany(Ulid $companyId): void
    {
        parent::switchCompany($companyId);

        $company = $this->registry->getRepository(Company::class)->find($companyId);
        $customDomain = $company?->getCustomDomain();

        if (is_string($customDomain) && $customDomain !== '') {
            $this->requestContext->setHost($customDomain);
            $this->requestContext->setScheme('https');
            $this->requestContext->setHttpsPort(443);
            return;
        }

        $this->applyApplicationUrl();
    }

    private function applyApplicationUrl(): void
    {
        if ($this->applicationUrl === '') {
            return;
        }

        $host = parse_url($this->applicationUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return;
        }

        $this->requestContext->setHost(rtrim(strtolower($host), '.'));

        $scheme = parse_url($this->applicationUrl, PHP_URL_SCHEME);
        $resolvedScheme = is_string($scheme) && $scheme !== '' ? strtolower($scheme) : 'https';
        $this->requestContext->setScheme($resolvedScheme);

        $port = parse_url($this->applicationUrl, PHP_URL_PORT);
        if (is_int($port)) {
            if ($resolvedScheme === 'https') {
                $this->requestContext->setHttpsPort($port);
            } else {
                $this->requestContext->setHttpPort($port);
            }
            return;
        }

        if ($resolvedScheme === 'https') {
            $this->requestContext->setHttpsPort(443);
        } else {
            $this->requestContext->setHttpPort(80);
        }
    }
}
