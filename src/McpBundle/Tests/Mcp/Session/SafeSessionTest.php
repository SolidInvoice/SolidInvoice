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

namespace SolidInvoice\McpBundle\Tests\Mcp\Session;

use Mcp\Server\Session\SessionStoreInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\McpBundle\Mcp\Session\SafeSession;
use Symfony\Component\Uid\UuidV4;

/**
 * @covers \SolidInvoice\McpBundle\Mcp\Session\SafeSession
 */
final class SafeSessionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSaveDoesNotCrashOnFreshSessionWithoutGetOrSet(): void
    {
        // Locks in the workaround for the upstream Session::save() bug.
        // A fresh Session that never had get/set/hydrate called must not blow
        // up with a "typed property must not be accessed before initialization"
        // fatal when save() runs.
        $store = M::mock(SessionStoreInterface::class);
        $id = new UuidV4();

        $store->shouldReceive('write')
            ->once()
            ->with(M::on(static fn ($passedId): bool => $passedId === $id), '[]')
            ->andReturn(true);

        $store->shouldReceive('read')
            ->once()
            ->with(M::on(static fn ($passedId): bool => $passedId === $id))
            ->andReturn(false);

        $session = new SafeSession($store, $id);

        self::assertTrue($session->save());
    }

    public function testSavePersistsExistingHydratedData(): void
    {
        $store = M::mock(SessionStoreInterface::class);
        $id = new UuidV4();

        $payload = ['initialized' => true, 'protocol_version' => '2025-11-25'];

        $store->shouldReceive('write')
            ->once()
            ->with(M::on(static fn ($passedId): bool => $passedId === $id), json_encode($payload))
            ->andReturn(true);

        $session = new SafeSession($store, $id);
        $session->hydrate($payload);

        self::assertTrue($session->save());
    }

    public function testSavePersistsValuesSetViaSet(): void
    {
        $store = M::mock(SessionStoreInterface::class);
        $id = new UuidV4();

        $store->shouldReceive('read')
            ->once()
            ->andReturn(false);

        $store->shouldReceive('write')
            ->once()
            ->with(
                M::on(static fn ($passedId): bool => $passedId === $id),
                json_encode(['foo' => 'bar']),
            )
            ->andReturn(true);

        $session = new SafeSession($store, $id);
        $session->set('foo', 'bar');

        self::assertTrue($session->save());
    }
}
