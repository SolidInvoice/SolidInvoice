<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Test\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<RecurringInvoice>
 *
 * @method static RecurringInvoice|Proxy createOne(array $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] createSequence(array|callable $sequence)
 * @method static RecurringInvoice|Proxy find(object|array|mixed $criteria)
 * @method static RecurringInvoice|Proxy findOrCreate(array $attributes)
 * @method static RecurringInvoice|Proxy first(string $sortedField = 'id')
 * @method static RecurringInvoice|Proxy last(string $sortedField = 'id')
 * @method static RecurringInvoice|Proxy random(array $attributes = [])
 * @method static RecurringInvoice|Proxy randomOrCreate(array $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] all()
 * @method static RecurringInvoice[]|Proxy[] findBy(array $attributes)
 * @method static RecurringInvoice[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static RecurringInvoice[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static RecurringInvoiceRepository|RepositoryProxy repository()
 * @method RecurringInvoice|Proxy create(array|callable $attributes = [])
 */
final class RecurringInvoiceFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     * @throws MathException
     */
    protected function getDefaults(): array
    {
        return [
            'client' => ClientFactory::new(),
            'status' => self::faker()->word(),
            'terms' => self::faker()->text(),
            'notes' => self::faker()->text(),
            'archived' => null,
            'dateStart' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
            'total' => BigInteger::of(self::faker()->randomNumber()),
            'baseTotal' => BigInteger::of(self::faker()->randomNumber()),
            'tax' => BigInteger::of(self::faker()->randomNumber()),
            'discount' => (new Discount())
                ->setType(self::faker()->text())
                ->setValueMoney(BigInteger::of(self::faker()->randomNumber()))
                ->setValuePercentage(self::faker()->randomFloat()),
        ];
    }

    protected static function getClass(): string
    {
        return RecurringInvoice::class;
    }
}
