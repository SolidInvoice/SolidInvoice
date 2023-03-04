<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Test\Factory;

use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Payment>
 *
 * @method static Payment|Proxy createOne(array $attributes = [])
 * @method static Payment[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Payment[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Payment|Proxy find(object|array|mixed $criteria)
 * @method static Payment|Proxy findOrCreate(array $attributes)
 * @method static Payment|Proxy first(string $sortedField = 'id')
 * @method static Payment|Proxy last(string $sortedField = 'id')
 * @method static Payment|Proxy random(array $attributes = [])
 * @method static Payment|Proxy randomOrCreate(array $attributes = [])
 * @method static Payment[]|Proxy[] all()
 * @method static Payment[]|Proxy[] findBy(array $attributes)
 * @method static Payment[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Payment[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static PaymentRepository|RepositoryProxy repository()
 * @method Payment|Proxy create(array|callable $attributes = [])
 */
final class PaymentFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        return [
            'number' => self::faker()->text(),
            'description' => self::faker()->text(),
            'clientEmail' => self::faker()->text(),
            'clientId' => self::faker()->text(),
            'totalAmount' => self::faker()->randomNumber(),
            'currencyCode' => self::faker()->currencyCode(),
            'details' => [],
            'status' => self::faker()->text(),
            'message' => self::faker()->text(),
            'completed' => self::faker()->dateTime(),
            'created' => self::faker()->dateTime(),
            'updated' => self::faker()->dateTime(),
        ];
    }

    protected static function getClass(): string
    {
        return Payment::class;
    }
}
