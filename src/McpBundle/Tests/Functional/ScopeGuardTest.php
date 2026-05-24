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

namespace SolidInvoice\McpBundle\Tests\Functional;

use Doctrine\Persistence\ManagerRegistry;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\McpSecurityContext;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(McpScopeGuard::class)]
#[CoversClass(McpSecurityContext::class)]
#[CoversClass(McpScope::class)]
final class ScopeGuardTest extends TestCase
{
    public function testReadScopeSatisfiesReadRequirement(): void
    {
        $this->buildGuard(['mcp:read'])->require(McpScope::Read);

        self::addToAssertionCount(1);
    }

    public function testWriteScopeImpliesRead(): void
    {
        $this->buildGuard(['mcp:write'])->require(McpScope::Read);

        self::addToAssertionCount(1);
    }

    public function testWriteScopeSatisfiesWriteRequirement(): void
    {
        $this->buildGuard(['mcp:write'])->require(McpScope::Write);

        self::addToAssertionCount(1);
    }

    public function testReadOnlyTokenCannotWrite(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('mcp:write');

        $this->buildGuard(['mcp:read'])->require(McpScope::Write);
    }

    public function testNoScopesDeniesAccess(): void
    {
        $this->expectException(ToolCallException::class);

        $this->buildGuard([])->require(McpScope::Read);
    }

    /**
     * @param list<string> $scopes
     */
    private function buildGuard(array $scopes): McpScopeGuard
    {
        $request = new Request();
        $request->attributes->set(McpOAuthAuthenticator::ATTR_SCOPES, $scopes);

        $stack = new RequestStack([$request]);

        $selector = new CompanySelector($this->createStub(ManagerRegistry::class));

        return new McpScopeGuard(new McpSecurityContext($stack, $selector));
    }
}
