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

namespace SolidInvoice\McpBundle\Mcp;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

/**
 * Exposes the OAuth scopes and company tied to the current MCP request.
 *
 * The {@see McpOAuthAuthenticator} sets these attributes on the Request during
 * firewall authentication; tools read them via this service.
 */
final class McpSecurityContext
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CompanySelector $companySelector,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return [];
        }

        $scopes = $request->attributes->get(McpOAuthAuthenticator::ATTR_SCOPES, []);

        return \is_array($scopes) ? array_values(array_filter($scopes, 'is_string')) : [];
    }

    public function getCompanyId(): ?Ulid
    {
        return $this->companySelector->getCompany();
    }
}
