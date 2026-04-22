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

namespace SolidInvoice\SettingsBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Security\McpScope;

final class SettingsReadTools
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Basic information about the company this token is bound to.
     *
     * @return array{id: string, name: string, currency: string|null}
     */
    #[McpTool(name: 'get_company_info', description: 'Return id, name, and default currency for the active company.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getCompanyInfo(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $companyId = $this->companySelector->getCompany();

        if ($companyId === null) {
            throw new ToolCallException('No active company on this request.');
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company instanceof Company) {
            throw new ToolCallException('Active company not found.');
        }

        return [
            'id' => $company->getId()->toRfc4122(),
            'name' => $company->getName(),
            'currency' => $company->currency,
        ];
    }
}
