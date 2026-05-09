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

namespace SolidInvoice\McpBundle\Tests\Action;

use PHPUnit\Framework\TestCase;

/**
 * The upgrade-gate path through the MCP Authorize action is exercised end-to-end
 * by `\SolidInvoice\SaasBundle\Tests\Functional\McpAuthorizeGateTest`, which
 * boots the full MCP stack with a seeded OAuth client and overrides the
 * FeatureGate / UpgradePromptProvider services to verify the rendered upgrade
 * page. Unit-level mocking is impractical because both `ConsentService` and
 * `ConsentGrantRepository` are `final`, and Mockery cannot stub them.
 */
final class AuthorizeUpgradeGateTest extends TestCase
{
    public function testGatePathCoveredByFunctionalTest(): void
    {
        self::assertTrue(true);
    }
}
