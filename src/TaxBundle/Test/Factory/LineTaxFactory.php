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
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Repository\LineTaxRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method LineTax|Proxy<LineTax> create((array | callable) $attributes = [])
 * @method static LineTax|Proxy<LineTax> createOne(array $attributes = [])
 * @method static LineTax|Proxy<LineTax> find((object | array | mixed) $criteria)
 * @method static LineTax|Proxy<LineTax> findOrCreate(array $attributes)
 * @method static LineTax|Proxy<LineTax> first(string $sortedField = 'id')
 * @method static LineTax|Proxy<LineTax> last(string $sortedField = 'id')
 * @method static LineTax|Proxy<LineTax> random(array $attributes = [])
 * @method static LineTax|Proxy<LineTax> randomOrCreate(array $attributes = [])
 * @method static LineTax[]|Proxy<LineTax>[] all()
 * @method static LineTax[]|Proxy<LineTax>[] createMany(int $number, (array | callable) $attributes = [])
 * @method static LineTax[]|Proxy<LineTax>[] createSequence((iterable | callable) $sequence)
 * @method static LineTax[]|Proxy<LineTax>[] findBy(array $attributes)
 * @method static LineTax[]|Proxy<LineTax>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static LineTax[]|Proxy<LineTax>[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(LineTax | Proxy<LineTax>)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(LineTax | Proxy<LineTax>)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<LineTax, LineTaxRepository> repository()
 *
 * @phpstan-method LineTax&Proxy<LineTax> create((array | callable) $attributes = [])
 * @phpstan-method static LineTax&Proxy<LineTax> createOne(array $attributes = [])
 * @extends PersistentProxyObjectFactory<LineTax>
 */
final class LineTaxFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'nameSnapshot' => self::faker()->randomElement(['VAT', 'GST', 'Sales Tax']),
            'rateSnapshot' => self::faker()->randomFloat(4, 0, 25),
            'categorySnapshot' => TaxCategory::Standard,
            'typeSnapshot' => self::faker()->randomElement(TaxType::cases()),
            'compound' => false,
            'sequence' => 0,
            'amount' => 0,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return LineTax::class;
    }
}
