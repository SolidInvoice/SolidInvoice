<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Test\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Quote>
 *
 * @method static Quote|Proxy createOne(array $attributes = [])
 * @method static Quote[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Quote[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Quote|Proxy find(object|array|mixed $criteria)
 * @method static Quote|Proxy findOrCreate(array $attributes)
 * @method static Quote|Proxy first(string $sortedField = 'id')
 * @method static Quote|Proxy last(string $sortedField = 'id')
 * @method static Quote|Proxy random(array $attributes = [])
 * @method static Quote|Proxy randomOrCreate(array $attributes = [])
 * @method static Quote[]|Proxy[] all()
 * @method static Quote[]|Proxy[] findBy(array $attributes)
 * @method static Quote[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Quote[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static QuoteRepository|RepositoryProxy repository()
 * @method Quote|Proxy create(array|callable $attributes = [])
 */
final class QuoteFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function getDefaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'company' => CompanyFactory::new(),
            'uuid' => Uuid::fromString(self::faker()->uuid()),
            'due' => self::faker()->dateTime(),
            'status' => self::faker()->word(),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => self::faker()->boolean(),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => (new Discount())
                ->setType(self::faker()->text())
                ->setValueMoney(self::faker()->randomNumber())
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    protected static function getClass(): string
    {
        return Quote::class;
    }
}
