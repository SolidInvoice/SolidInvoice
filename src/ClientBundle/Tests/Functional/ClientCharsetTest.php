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

namespace SolidInvoice\ClientBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * A `UTF8` (utf8mb3) connection charset silently mangles 4-byte characters on write,
 * where a schema-only utf8mb4 conversion would not catch it. This is the regression
 * DatabaseCharsetEnvVarProcessor exists to prevent (SOL-259).
 */
#[Group('functional')]
final class ClientCharsetTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testA4ByteCharacterSurvivesAWriteAndReread(): void
    {
        $name = '吉田 𠮷 😀';

        $client = ClientFactory::createOne(['name' => $name]);
        $id = $client->getId();

        $this->em->flush();
        $this->em->clear();
        self::assertInstanceOf(Ulid::class, $id);

        $reloaded = $this->em->find(Client::class, $id);

        self::assertInstanceOf(Client::class, $reloaded);
        self::assertSame($name, $reloaded->getName());
    }
}
