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

namespace SolidInvoice\TaxBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Repository\InvoiceTaxRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method InvoiceTax|Proxy<InvoiceTax> create((array | callable) $attributes = [])
 * @method static InvoiceTax|Proxy<InvoiceTax> createOne(array $attributes = [])
 * @method static InvoiceTax|Proxy<InvoiceTax> find((object | array | mixed) $criteria)
 * @method static InvoiceTax|Proxy<InvoiceTax> findOrCreate(array $attributes)
 * @method static InvoiceTax|Proxy<InvoiceTax> first(string $sortedField = 'id')
 * @method static InvoiceTax|Proxy<InvoiceTax> last(string $sortedField = 'id')
 * @method static InvoiceTax|Proxy<InvoiceTax> random(array $attributes = [])
 * @method static InvoiceTax|Proxy<InvoiceTax> randomOrCreate(array $attributes = [])
 * @method static InvoiceTax[]|Proxy<InvoiceTax>[] all()
 * @method static InvoiceTax[]|Proxy<InvoiceTax>[] createMany(int $number, (array | callable) $attributes = [])
 * @method static InvoiceTax[]|Proxy<InvoiceTax>[] createSequence((iterable | callable) $sequence)
 * @method static InvoiceTax[]|Proxy<InvoiceTax>[] findBy(array $attributes)
 * @method static InvoiceTax[]|Proxy<InvoiceTax>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static InvoiceTax[]|Proxy<InvoiceTax>[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(InvoiceTax | Proxy<InvoiceTax>)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(InvoiceTax | Proxy<InvoiceTax>)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<InvoiceTax, InvoiceTaxRepository> repository()
 *
 * @phpstan-method InvoiceTax&Proxy<InvoiceTax> create((array | callable) $attributes = [])
 * @phpstan-method static InvoiceTax&Proxy<InvoiceTax> createOne(array $attributes = [])
 * @extends PersistentProxyObjectFactory<InvoiceTax>
 */
final class InvoiceTaxFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'nameSnapshot' => self::faker()->randomElement(['Withholding', 'Reverse Charge', 'Surcharge']),
            'rateSnapshot' => self::faker()->randomFloat(4, 0, 25),
            'categorySnapshot' => TaxCategory::Standard,
            'direction' => self::faker()->randomElement(TaxDirection::cases()),
            'sequence' => 0,
            'amount' => 0,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return InvoiceTax::class;
    }
}
