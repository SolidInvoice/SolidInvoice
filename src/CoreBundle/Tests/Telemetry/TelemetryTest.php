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

namespace SolidInvoice\CoreBundle\Tests\Telemetry;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;

#[CoversClass(Telemetry::class)]
final class TelemetryTest extends TestCase
{
    private CollectingMessageBus $bus;

    protected function setUp(): void
    {
        $this->bus = new CollectingMessageBus();
    }

    public function testEventDoesNothingWhenDisabledByFlag(): void
    {
        $this->createTelemetry(enableTelemetry: false)->event(TelemetryEvent::ClientCreated);

        self::assertSame([], $this->bus->messages);
    }

    public function testEventDoesNothingWhenBuildIdIsEmpty(): void
    {
        $this->createTelemetry(buildId: '')->event(TelemetryEvent::ClientCreated);

        self::assertSame([], $this->bus->messages);
    }

    public function testEventBuildsTheCorrectPayload(): void
    {
        $this->createTelemetry()->event(TelemetryEvent::PaymentReceived, ['gateway' => 'stripe']);

        self::assertCount(1, $this->bus->messages);

        $message = $this->bus->messages[0];

        self::assertSame('event', $message->type);
        self::assertSame([
            'build_id' => 'build-123',
            'app' => 'solidinvoice',
            'event' => 'payment_received',
            'properties' => ['gateway' => 'stripe'],
        ], $message->payload);
    }

    public function testPingBuildsTheFullEnvironmentPayload(): void
    {
        $this->createTelemetry()->ping();

        self::assertCount(1, $this->bus->messages);

        $message = $this->bus->messages[0];

        self::assertSame('ping', $message->type);
        self::assertSame('build-123', $message->payload['build_id']);
        self::assertSame('solidinvoice', $message->payload['app']);
        self::assertSame(SolidInvoiceCoreBundle::VERSION, $message->payload['version']);
        self::assertArrayHasKey('os', $message->payload);
        self::assertArrayHasKey('os_version', $message->payload);
        self::assertArrayHasKey('php_version', $message->payload);
        self::assertArrayHasKey('install_type', $message->payload);

        // The driver and version are derived from the Doctrine connection, never
        // from the database URL, so credentials can never leak into the payload.
        self::assertSame('en', $message->payload['properties']['locale']);
        self::assertSame('sqlite', $message->payload['properties']['db_driver']);
        self::assertNotEmpty($message->payload['properties']['db_version']);

        // No PII fields are ever included.
        self::assertArrayNotHasKey('geo', $message->payload);
        self::assertArrayNotHasKey('ip', $message->payload);
    }

    /**
     * @return iterable<string, array{AbstractPlatform, string}>
     */
    public static function databasePlatformProvider(): iterable
    {
        // MariaDBPlatform extends MySQLPlatform, so it must resolve to mariadb.
        yield 'mariadb' => [new MariaDBPlatform(), 'mariadb'];
        yield 'mysql' => [new MySQLPlatform(), 'mysql'];
        yield 'postgresql' => [new PostgreSQLPlatform(), 'pgsql'];
        yield 'sqlite' => [new SqlitePlatform(), 'sqlite'];
    }

    #[DataProvider('databasePlatformProvider')]
    public function testPingMapsDatabasePlatformToDriverName(AbstractPlatform $platform, string $expectedDriver): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $this->createTelemetry(connection: $connection)->ping();

        self::assertSame($expectedDriver, $this->bus->messages[0]->payload['properties']['db_driver']);
    }

    public function testPingDetectsDockerInstallTypeFromEnv(): void
    {
        $this->createTelemetry(docker: true)->ping();

        self::assertSame('docker', $this->bus->messages[0]->payload['install_type']);
    }

    public function testPingDefaultsToManualInstallTypeWithoutDocker(): void
    {
        $this->createTelemetry(docker: false)->ping();

        self::assertSame('manual', $this->bus->messages[0]->payload['install_type']);
    }

    public function testPingPrefersExplicitInstallTypeOverDockerDetection(): void
    {
        $this->createTelemetry(installType: 'kubernetes', docker: true)->ping();

        self::assertSame('kubernetes', $this->bus->messages[0]->payload['install_type']);
    }

    public function testPingEmitsUpdateEventWhenStoredVersionDiffers(): void
    {
        $this->createTelemetry(lastVersion: '2.9.0')->ping();

        self::assertCount(2, $this->bus->messages);

        [$update, $ping] = $this->bus->messages;

        self::assertSame('event', $update->type);
        self::assertSame('update', $update->payload['event']);
        self::assertSame([
            'from_version' => '2.9.0',
            'to_version' => SolidInvoiceCoreBundle::VERSION,
        ], $update->payload['properties']);

        self::assertSame('ping', $ping->type);
    }

    public function testPingDoesNotEmitUpdateEventWhenVersionIsUnchanged(): void
    {
        $this->createTelemetry(lastVersion: SolidInvoiceCoreBundle::VERSION)->ping();

        self::assertCount(1, $this->bus->messages);
        self::assertSame('ping', $this->bus->messages[0]->type);
    }

    public function testPingDoesNotEmitUpdateEventOnFirstRun(): void
    {
        $this->createTelemetry(lastVersion: null)->ping();

        self::assertCount(1, $this->bus->messages);
        self::assertSame('ping', $this->bus->messages[0]->type);
    }

    private function createTelemetry(
        ?string $buildId = 'build-123',
        bool $enableTelemetry = true,
        string $installType = '',
        bool $docker = false,
        string $locale = 'en',
        ?Connection $connection = null,
        ?string $lastVersion = null,
    ): Telemetry {
        $vault = $this->createMock(AbstractVault::class);
        $vault->method('generateKeys')->willReturn(true);

        $configWriter = new ConfigWriter($vault, '/tmp/solidinvoice-test-config');

        return new Telemetry(
            $this->bus,
            $configWriter,
            $connection ?? DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $buildId,
            $enableTelemetry,
            $installType,
            $docker,
            $locale,
            $lastVersion,
        );
    }
}
