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
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method TaxIdentifier|Proxy<TaxIdentifier> create((array | callable) $attributes = [])
 * @method static TaxIdentifier|Proxy<TaxIdentifier> createOne(array $attributes = [])
 * @method static TaxIdentifier|Proxy<TaxIdentifier> find((object | array | mixed) $criteria)
 * @method static TaxIdentifier|Proxy<TaxIdentifier> findOrCreate(array $attributes)
 * @method static TaxIdentifier|Proxy<TaxIdentifier> first(string $sortedField = 'id')
 * @method static TaxIdentifier|Proxy<TaxIdentifier> last(string $sortedField = 'id')
 * @method static TaxIdentifier|Proxy<TaxIdentifier> random(array $attributes = [])
 * @method static TaxIdentifier|Proxy<TaxIdentifier> randomOrCreate(array $attributes = [])
 * @method static TaxIdentifier[]|Proxy<TaxIdentifier>[] all()
 * @method static TaxIdentifier[]|Proxy<TaxIdentifier>[] createMany(int $number, (array | callable) $attributes = [])
 * @method static TaxIdentifier[]|Proxy<TaxIdentifier>[] createSequence((iterable | callable) $sequence)
 * @method static TaxIdentifier[]|Proxy<TaxIdentifier>[] findBy(array $attributes)
 * @method static TaxIdentifier[]|Proxy<TaxIdentifier>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static TaxIdentifier[]|Proxy<TaxIdentifier>[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(TaxIdentifier | Proxy<TaxIdentifier>)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(TaxIdentifier | Proxy<TaxIdentifier>)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<TaxIdentifier, TaxIdentifierRepository> repository()
 *
 * @phpstan-method TaxIdentifier&Proxy<TaxIdentifier> create((array | callable) $attributes = [])
 * @phpstan-method static TaxIdentifier&Proxy<TaxIdentifier> createOne(array $attributes = [])
 * @extends PersistentProxyObjectFactory<TaxIdentifier>
 */
final class TaxIdentifierFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->randomElement(['VAT', 'GSTIN', 'TIN']),
            'value' => strtoupper(self::faker()->bothify('??#########')),
            'primary' => false,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return TaxIdentifier::class;
    }
}
