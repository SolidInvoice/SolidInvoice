<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Test\Factory;

use Money\Currency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Credit>
 *
 * @method static Credit|Proxy createOne(array $attributes = [])
 * @method static Credit[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Credit[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Credit|Proxy find(object|array|mixed $criteria)
 * @method static Credit|Proxy findOrCreate(array $attributes)
 * @method static Credit|Proxy first(string $sortedField = 'id')
 * @method static Credit|Proxy last(string $sortedField = 'id')
 * @method static Credit|Proxy random(array $attributes = [])
 * @method static Credit|Proxy randomOrCreate(array $attributes = [])
 * @method static Credit[]|Proxy[] all()
 * @method static Credit[]|Proxy[] findBy(array $attributes)
 * @method static Credit[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Credit[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static CreditRepository|RepositoryProxy repository()
 * @method Credit|Proxy create(array|callable $attributes = [])
 */
final class CreditFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'value' => new Money(self::faker()->randomNumber(), new Currency(self::faker()->currencyCode())),
            'company' => CompanyFactory::new(),
        ];
    }

    protected static function getClass(): string
    {
        return Credit::class;
    }
}
