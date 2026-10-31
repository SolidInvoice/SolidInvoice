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

namespace SolidInvoice\EInvoiceBundle\Tests\Profile;

use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Exception\UnknownProfileException;
use SolidInvoice\EInvoiceBundle\Profile\ProfileInterface;
use SolidInvoice\EInvoiceBundle\Profile\ProfileRegistry;
use Symfony\Contracts\Translation\TranslatableInterface;

final class ProfileRegistryTest extends TestCase
{
    public function testGetReturnsRegisteredProfile(): void
    {
        $profile = $this->profile('en16931');
        $registry = new ProfileRegistry([$profile]);

        self::assertSame($profile, $registry->get('en16931'));
    }

    public function testGetThrowsForUnknownIdentifier(): void
    {
        $registry = new ProfileRegistry([]);

        $this->expectException(UnknownProfileException::class);

        $registry->get('unknown');
    }

    public function testHasReturnsTrueAndFalse(): void
    {
        $registry = new ProfileRegistry([$this->profile('en16931')]);

        self::assertTrue($registry->has('en16931'));
        self::assertFalse($registry->has('xrechnung'));
    }

    public function testAllPreservesOrder(): void
    {
        $first = $this->profile('en16931');
        $second = $this->profile('xrechnung-3.0.2');

        $registry = new ProfileRegistry([$first, $second]);

        self::assertSame([$first, $second], $registry->all());
    }

    private function profile(string $identifier): ProfileInterface
    {
        $profile = $this->createStub(ProfileInterface::class);
        $profile->method('getIdentifier')->willReturn($identifier);
        $profile->method('getSyntax')->willReturn(SyntaxFormat::Ubl);
        $profile->method('getLabel')->willReturn($this->createStub(TranslatableInterface::class));

        return $profile;
    }
}
