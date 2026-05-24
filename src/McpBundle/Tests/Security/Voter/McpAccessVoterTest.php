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

namespace SolidInvoice\McpBundle\Tests\Security\Voter;

use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\McpBundle\Security\Attribute;
use SolidInvoice\McpBundle\Security\Voter\McpAccessVoter;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(McpAccessVoter::class)]
final class McpAccessVoterTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGrantsWhenSaasIsDisabled(): void
    {
        $voter = new McpAccessVoter($this->toggler(saasEnabled: false), new NoopFeatureGate());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(M::mock(TokenInterface::class), null, [Attribute::ACCESS]),
        );
    }

    public function testAbstainsWhenSaasIsEnabled(): void
    {
        $voter = new McpAccessVoter($this->toggler(saasEnabled: true), new NoopFeatureGate());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(M::mock(TokenInterface::class), null, [Attribute::ACCESS]),
        );
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = new McpAccessVoter($this->toggler(saasEnabled: false), new NoopFeatureGate());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(M::mock(TokenInterface::class), null, ['ROLE_USER']),
        );
    }

    public function testDeniesWhenFeatureGateDeniesMcpAccess(): void
    {
        $featureGate = M::mock(FeatureGate::class);
        $featureGate->shouldReceive('isEnabled')->with('mcp_access')->andReturn(false);

        $voter = new McpAccessVoter($this->toggler(saasEnabled: false), $featureGate);

        $vote = new Vote();
        $result = $voter->vote(M::mock(TokenInterface::class), null, [Attribute::ACCESS], $vote);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
        self::assertSame(['MCP access is not available on the current plan.'], $vote->reasons);
    }

    private function toggler(bool $saasEnabled): ToggleInterface
    {
        $toggler = M::mock(ToggleInterface::class);
        $toggler->shouldReceive('isActive')->with('saas_enabled')->andReturn($saasEnabled);

        return $toggler;
    }
}
