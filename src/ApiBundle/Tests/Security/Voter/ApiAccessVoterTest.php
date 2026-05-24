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

namespace SolidInvoice\ApiBundle\Tests\Security\Voter;

use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Security\Attribute;
use SolidInvoice\ApiBundle\Security\Voter\ApiAccessVoter;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(ApiAccessVoter::class)]
final class ApiAccessVoterTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGrantsWhenSaasIsDisabled(): void
    {
        $voter = new ApiAccessVoter($this->toggler(saasEnabled: false), new NoopFeatureGate());

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(M::mock(TokenInterface::class), null, [Attribute::ACCESS]),
        );
    }

    public function testAbstainsWhenSaasIsEnabled(): void
    {
        $voter = new ApiAccessVoter($this->toggler(saasEnabled: true), new NoopFeatureGate());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(M::mock(TokenInterface::class), null, [Attribute::ACCESS]),
        );
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = new ApiAccessVoter($this->toggler(saasEnabled: false), new NoopFeatureGate());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(M::mock(TokenInterface::class), null, ['ROLE_USER']),
        );
    }

    public function testDeniesWhenFeatureGateDeniesRestApiAccess(): void
    {
        $featureGate = M::mock(FeatureGate::class);
        $featureGate->shouldReceive('isEnabled')->with('rest_api_access')->andReturn(false);

        $voter = new ApiAccessVoter($this->toggler(saasEnabled: false), $featureGate);

        $vote = new Vote();
        $result = $voter->vote(M::mock(TokenInterface::class), null, [Attribute::ACCESS], $vote);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
        self::assertSame(['REST API access is not available on the current plan.'], $vote->reasons);
    }

    private function toggler(bool $saasEnabled): ToggleInterface
    {
        $toggler = M::mock(ToggleInterface::class);
        $toggler->shouldReceive('isActive')->with('saas_enabled')->andReturn($saasEnabled);

        return $toggler;
    }
}
