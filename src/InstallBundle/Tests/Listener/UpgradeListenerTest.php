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

namespace SolidInvoice\InstallBundle\Tests\Listener;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\Version;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\InstallBundle\Listener\UpgradeListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use function fclose;
use function file_exists;
use function flock;
use function fopen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const LOCK_EX;
use const LOCK_NB;
use const LOCK_UN;

#[CoversClass(UpgradeListener::class)]
final class UpgradeListenerTest extends TestCase
{
    private string $lockFile;

    protected function setUp(): void
    {
        $this->lockFile = sys_get_temp_dir() . '/' . uniqid('solidinvoice_upgrade_', true) . '/db_upgrade.lock';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(['kernel.request' => ['onKernelRequest', 10]], UpgradeListener::getSubscribedEvents());
    }

    public function testDoesNothingWhenNotInstalled(): void
    {
        $factory = $this->createMock(DependencyFactory::class);
        $factory->expects(self::never())->method('getMigrationStatusCalculator');

        $listener = new UpgradeListener(null, new Migration($factory, $this->createStub(ManagerRegistry::class)), $this->lockFile);
        $listener->onKernelRequest($this->createEvent());

        self::assertFileDoesNotExist($this->lockFile);
    }

    public function testDoesNotLockWhenAlreadyUpToDate(): void
    {
        $factory = $this->createMock(DependencyFactory::class);
        $factory->method('getMigrationStatusCalculator')->willReturn($this->createStatusCalculator([true]));
        $factory->expects(self::never())->method('getMetadataStorage');

        $listener = new UpgradeListener('1', new Migration($factory, $this->createStub(ManagerRegistry::class)), $this->lockFile);
        $listener->onKernelRequest($this->createEvent());

        self::assertFileDoesNotExist($this->lockFile);
    }

    public function testSkipsMigrationWhenAnotherRequestFinishedItWhileWaitingForTheLock(): void
    {
        $lockFile = $this->lockFile;
        $lockWasHeld = null;

        $factory = $this->createMock(DependencyFactory::class);
        $factory->method('getMigrationStatusCalculator')->willReturn(
            $this->createStatusCalculator([false, true], static function (int $call) use ($lockFile, &$lockWasHeld): void {
                if (2 !== $call) {
                    return;
                }

                // The second check must run while the listener holds the lock.
                $handle = fopen($lockFile, 'c');
                self::assertNotFalse($handle);
                $lockWasHeld = ! flock($handle, LOCK_EX | LOCK_NB);
                fclose($handle);
            })
        );
        // migrate() starts by fetching the metadata storage, so it must not be called.
        $factory->expects(self::never())->method('getMetadataStorage');

        $listener = new UpgradeListener('1', new Migration($factory, $this->createStub(ManagerRegistry::class)), $this->lockFile);
        $listener->onKernelRequest($this->createEvent());

        self::assertTrue($lockWasHeld);

        // The lock is released afterwards.
        $handle = fopen($this->lockFile, 'c');
        self::assertNotFalse($handle);
        self::assertTrue(flock($handle, LOCK_EX | LOCK_NB));
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * @param list<bool> $upToDateResults
     * @param (callable(int): void)|null $onCheck
     */
    private function createStatusCalculator(array $upToDateResults, ?callable $onCheck = null): MigrationStatusCalculator
    {
        $call = 0;
        $calculator = $this->createStub(MigrationStatusCalculator::class);
        $calculator->method('getNewMigrations')->willReturn(new AvailableMigrationsList([]));
        $calculator->method('getExecutedUnavailableMigrations')->willReturnCallback(
            static function () use (&$call, $upToDateResults, $onCheck): ExecutedMigrationsList {
                ++$call;

                if (null !== $onCheck) {
                    $onCheck($call);
                }

                $upToDate = $upToDateResults[$call - 1] ?? true;

                return new ExecutedMigrationsList($upToDate ? [] : [new ExecutedMigration(new Version('Test'))]);
            }
        );

        return $calculator;
    }

    private function createEvent(): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST);
    }
}
