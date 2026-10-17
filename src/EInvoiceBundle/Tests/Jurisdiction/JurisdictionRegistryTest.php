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

namespace SolidInvoice\EInvoiceBundle\Tests\Jurisdiction;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Exception\UnknownJurisdictionException;
use SolidInvoice\EInvoiceBundle\Jurisdiction\JurisdictionRegistry;

final class JurisdictionRegistryTest extends TestCase
{
    public function testGetResolvesKnownCountryCode(): void
    {
        $registry = new JurisdictionRegistry(['DE' => $this->row()]);

        $definition = $registry->get('DE');

        self::assertSame('DE', $definition->countryCode);
        self::assertSame(SyntaxFormat::Cii, $definition->syntax);
        self::assertSame('xrechnung-3.0.2', $definition->defaultProfile);
    }

    public function testGetThrowsForUnknownCountryCode(): void
    {
        $registry = new JurisdictionRegistry([]);

        $this->expectException(UnknownJurisdictionException::class);

        $registry->get('ZZ');
    }

    public function testHasReturnsTrueAndFalse(): void
    {
        $registry = new JurisdictionRegistry(['DE' => $this->row()]);

        self::assertTrue($registry->has('DE'));
        self::assertFalse($registry->has('FR'));
    }

    #[DataProvider('caseVariants')]
    public function testLookupIsCaseInsensitive(string $countryCode): void
    {
        $registry = new JurisdictionRegistry(['DE' => $this->row()]);

        self::assertTrue($registry->has($countryCode));
        self::assertSame('DE', $registry->get($countryCode)->countryCode);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function caseVariants(): iterable
    {
        yield 'lower case' => ['de'];
        yield 'upper case' => ['DE'];
        yield 'mixed case' => ['De'];
    }

    public function testPermittedProfilesDefaultsToDefaultProfileWhenOmitted(): void
    {
        $row = $this->row();
        unset($row['permittedProfiles']);

        $registry = new JurisdictionRegistry(['DE' => $row]);

        self::assertSame(['xrechnung-3.0.2'], $registry->get('DE')->permittedProfiles);
    }

    public function testHydrationThrowsWhenDefaultProfileIsNotPermitted(): void
    {
        $row = $this->row();
        $row['permittedProfiles'] = ['en16931'];

        $this->expectException(InvalidArgumentException::class);

        new JurisdictionRegistry(['DE' => $row]);
    }

    public function testHydrationThrowsForUnknownKey(): void
    {
        $row = $this->row();
        $row['unknownKey'] = 'value';

        $this->expectException(InvalidArgumentException::class);

        new JurisdictionRegistry(['DE' => $row]);
    }

    public function testAllIsKeyedByUpperCaseCountryCode(): void
    {
        $registry = new JurisdictionRegistry(['de' => $this->row()]);

        self::assertArrayHasKey('DE', $registry->all());
    }

    /**
     * @return array{
     *     syntax: SyntaxFormat,
     *     defaultProfile: string,
     *     permittedProfiles: list<string>,
     *     mandatoryTerms: list<string>,
     *     identifierSchemes: array<string, string>,
     *     defaultChannel: string,
     * }
     */
    private function row(): array
    {
        return [
            'syntax' => SyntaxFormat::Cii,
            'defaultProfile' => 'xrechnung-3.0.2',
            'permittedProfiles' => ['xrechnung-3.0.2'],
            'mandatoryTerms' => ['BT-10'],
            'identifierSchemes' => ['eas' => '9958'],
            'defaultChannel' => 'peppol',
        ];
    }
}
