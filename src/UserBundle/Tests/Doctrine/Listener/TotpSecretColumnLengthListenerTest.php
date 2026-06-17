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

namespace SolidInvoice\UserBundle\Tests\Doctrine\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Doctrine\Listener\TotpSecretColumnLengthListener;
use SolidInvoice\UserBundle\Entity\User;
use stdClass;

#[CoversClass(TotpSecretColumnLengthListener::class)]
final class TotpSecretColumnLengthListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOverridesTotpSecretColumnLengthForUserEntity(): void
    {
        $classMetadata = new ClassMetadata(User::class);
        $classMetadata->fieldMappings['totpSecret'] = [
            'fieldName' => 'totpSecret',
            'type' => 'string',
            'length' => 45,
            'nullable' => true,
            'columnName' => 'totp_secret',
        ];

        $event = new LoadClassMetadataEventArgs($classMetadata, M::mock(EntityManagerInterface::class));

        (new TotpSecretColumnLengthListener())->loadClassMetadata($event);

        // scheb/2fa-totp v7+ generates 52-char Base32 secrets; old limit was 45
        self::assertGreaterThan(45, $classMetadata->fieldMappings['totpSecret']['length']);
        self::assertGreaterThanOrEqual(52, $classMetadata->fieldMappings['totpSecret']['length']);
    }

    public function testDoesNotModifyFieldsForOtherEntities(): void
    {
        $classMetadata = new ClassMetadata(stdClass::class);
        $classMetadata->fieldMappings['someField'] = [
            'fieldName' => 'someField',
            'type' => 'string',
            'length' => 45,
            'nullable' => true,
            'columnName' => 'some_field',
        ];

        $event = new LoadClassMetadataEventArgs($classMetadata, M::mock(EntityManagerInterface::class));

        (new TotpSecretColumnLengthListener())->loadClassMetadata($event);

        // Only the User entity's totpSecret column is widened; other entities are untouched
        self::assertArrayHasKey('someField', $classMetadata->fieldMappings);
        self::assertArrayNotHasKey('totpSecret', $classMetadata->fieldMappings);
    }

    public function testDoesNotThrowWhenUserEntityHasNoTotpSecretField(): void
    {
        $classMetadata = new ClassMetadata(User::class);

        $event = new LoadClassMetadataEventArgs($classMetadata, M::mock(EntityManagerInterface::class));

        (new TotpSecretColumnLengthListener())->loadClassMetadata($event);

        self::assertArrayNotHasKey('totpSecret', $classMetadata->fieldMappings);
    }
}
