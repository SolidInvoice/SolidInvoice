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

use League\Uri\Uri;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;
use function in_array;
use function rtrim;
use function strtolower;
use function trim;

final class CompanyDomainResolver implements ResetInterface
{
    private const LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]'];

    /**
     * @var array<string, ResolvedHost>
     */
    private array $cache = [];

    private bool $defaultHostParsed = false;

    private ?string $defaultHost = null;

    private string $defaultScheme = 'https';

    private int $defaultPort = 443;

    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly string $applicationUrl = '',
    ) {
    }

    public function resolve(string $host): ResolvedHost
    {
        $host = rtrim(strtolower($host), '.');

        if (isset($this->cache[$host])) {
            return $this->cache[$host];
        }

        $this->parseDefaultHost();

        if ($this->defaultHost === null || $host === $this->defaultHost || $this->isLoopbackHost($host)) {
            return $this->cache[$host] = new ResolvedHost(
                HostType::DefaultHost,
                $this->defaultHost ?? $host,
                $this->defaultScheme,
                $this->defaultPort,
            );
        }

        $company = $host === '' ? null : $this->companyRepository->findOneByCustomDomain($host);

        if ($company !== null) {
            return $this->cache[$host] = new ResolvedHost(
                HostType::CustomDomain,
                $host,
                'https',
                443,
                $company,
            );
        }

        return $this->cache[$host] = new ResolvedHost(
            HostType::Unknown,
            $host,
            $this->defaultScheme,
            $this->defaultPort,
        );
    }

    public function reset(): void
    {
        $this->cache = [];
        $this->defaultHostParsed = false;
        $this->defaultHost = null;
    }

    private function isLoopbackHost(string $host): bool
    {
        return in_array(trim($host, '[]'), ['localhost', '127.0.0.1', '::1'], true)
            || in_array($host, self::LOOPBACK_HOSTS, true);
    }

    private function parseDefaultHost(): void
    {
        if ($this->defaultHostParsed) {
            return;
        }

        $this->defaultHostParsed = true;

        if ($this->applicationUrl === '') {
            return;
        }

        try {
            $uri = Uri::new($this->applicationUrl);
        } catch (Throwable) {
            return;
        }

        $host = $uri->getHost();

        if ($host === null || $host === '') {
            return;
        }

        $this->defaultHost = rtrim(strtolower($host), '.');

        $scheme = $uri->getScheme();
        if ($scheme !== null && $scheme !== '') {
            $this->defaultScheme = strtolower($scheme);
        }

        $port = $uri->getPort();
        $this->defaultPort = $port ?? ($this->defaultScheme === 'http' ? 80 : 443);
    }
}
