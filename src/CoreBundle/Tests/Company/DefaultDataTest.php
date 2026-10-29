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

namespace SolidInvoice\CoreBundle\Tests\Company;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery as M;
use Mpociot\VatCalculator\VatCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\DefaultData;
use SolidInvoice\CoreBundle\Config\SystemConfigProvider;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\InvoiceBundle\Config\ConfigProvider as InvoiceConfigProvider;
use SolidInvoice\MailerBundle\Config\ConfigProvider as MailerConfigProvider;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Config\ConfigProvider as QuoteConfigProvider;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use function array_map;

final class DefaultDataTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testDefaultData(): void
    {
        $registry = M::mock(ManagerRegistry::class);
        $entityManager = M::mock(EntityManagerInterface::class);

        $registry
            ->expects('getManager')
            ->andReturn($entityManager);

        $entityManager
            ->expects('persist')
            ->with(M::type(Setting::class))
            ->times(25);

        $entityManager->expects('persist')
            ->with(M::type(CustomField::class))
            ->times(3);

        $entityManager->expects('persist')
            ->with(M::type(PaymentMethod::class))
            ->times(5);

        $entityManager
            ->expects('flush')
            ->once();

        $company = new Company();
        $company->setName('Test Company');

        // No country, so no tax rates: a persisted Tax would match none of the expectations.
        $this->createDefaultData($registry)($company, ['currency' => 'EUR']);
    }

    public function testCreatesOneTaxRatePerDistinctVatRateOfTheCountry(): void
    {
        $taxes = $this->persistedTaxesFor('AT');

        // Austria's 13% is listed twice (low1 and parking) and must only be created once.
        self::assertSame(['VAT 20%', 'VAT 10%', 'VAT 13%'], array_map(static fn (Tax $tax): ?string => $tax->getName(), $taxes));
        self::assertSame([20.0, 10.0, 13.0], array_map(static fn (Tax $tax): ?float => $tax->getRate(), $taxes));
        self::assertSame(TaxType::Exclusive->value, $taxes[0]->getType());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function otherCountryProvider(): iterable
    {
        yield 'South Africa' => ['ZA', ['VAT 15%']];
        yield 'United Kingdom' => ['GB', ['VAT 20%', 'VAT 5%']];
        yield 'Australia' => ['AU', ['GST 10%']];
        // Sales tax is set per state, so there is no national rate to start from.
        yield 'United States' => ['US', []];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('otherCountryProvider')]
    public function testCreatesTaxRatesForCountriesTheVatCalculatorDoesNotKnow(string $country, array $expected): void
    {
        self::assertSame($expected, array_map(static fn (Tax $tax): ?string => $tax->getName(), $this->persistedTaxesFor($country)));
    }

    /**
     * @return list<Tax>
     */
    private function persistedTaxesFor(string $country): array
    {
        $registry = M::mock(ManagerRegistry::class);
        $entityManager = M::mock(EntityManagerInterface::class);
        $registry->allows('getManager')->andReturn($entityManager);
        $entityManager->allows('flush');

        $taxes = [];
        $entityManager->allows('persist')->andReturnUsing(static function (object $entity) use (&$taxes): void {
            if ($entity instanceof Tax) {
                $taxes[] = $entity;
            }
        });

        $company = new Company();
        $company->setName('Test Company');
        $company->setCountry($country);

        $this->createDefaultData($registry)($company, ['currency' => 'EUR']);

        return $taxes;
    }

    private function createDefaultData(ManagerRegistry $registry): DefaultData
    {
        // Stands in for the catalog entry, so the assertions read like what users see.
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['tax.default_rate.gst' => 'GST %rate%%', 'tax.default_rate.vat' => 'VAT %rate%%'], 'en');

        return new DefaultData(
            $registry,
            [
                new SystemConfigProvider(),
                new InvoiceConfigProvider(),
                new QuoteConfigProvider(),
                new MailerConfigProvider(),
            ],
            new VatCalculator(),
            $translator,
        );
    }
}
