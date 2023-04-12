<?php
declare(strict_types=1);

namespace SolidInvoice\PaymentBundle\Tests\Payum\Storage;

use Doctrine\Persistence\ObjectManager;
use LogicException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SolidInvoice\PaymentBundle\Payum\Storage\DoctrineStorage;
use Doctrine\Persistence\Mapping\ClassMetadata;
use stdClass;

final class DoctrineStorageTest extends TestCase
{
    public function testGetIdentity(): void
    {
        $objectManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $objectManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(stdClass::class)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(new stdClass())
            ->willReturn(['id' => '123']);

        $storage = new DoctrineStorage($objectManager, stdClass::class);

        $identity = $storage->identify(new stdClass());

        self::assertSame('123', $identity->getId());
        self::assertSame(stdClass::class, $identity->getClass());
    }

    public function testGetIdentityWithUuid(): void
    {
        $objectManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $objectManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(stdClass::class)
            ->willReturn($classMetadata);

        $uuid = Uuid::uuid4();

        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(new stdClass())
            ->willReturn(['id' => $uuid]);

        $storage = new DoctrineStorage($objectManager, stdClass::class);

        $identity = $storage->identify(new stdClass());

        self::assertSame($uuid->toString(), $identity->getId());
        self::assertSame(stdClass::class, $identity->getClass());
    }

    public function testGetIdentityWithCompositeKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Storage does not support composite primary ids');

        $objectManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $objectManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(stdClass::class)
            ->willReturn($classMetadata);

        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(new stdClass())
            ->willReturn(['id' => '123', 'id2' => '456']);

        $storage = new DoctrineStorage($objectManager, stdClass::class);

        $storage->identify(new stdClass());
    }
}
