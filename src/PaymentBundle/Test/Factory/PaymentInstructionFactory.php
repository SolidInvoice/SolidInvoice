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

namespace SolidInvoice\PaymentBundle\Test\Factory;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\PaymentBundle\Entity\PaymentInstruction;
use SolidInvoice\PaymentBundle\Enum\PaymentMeans;
use SolidInvoice\PaymentBundle\Repository\PaymentInstructionRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

/**
 * @method PaymentInstruction create((array<string, mixed> | callable) $attributes = [])
 * @method static PaymentInstruction createOne(array<string, mixed> $attributes = [])
 * @method static PaymentInstruction find((object | array<string, mixed> | mixed) $criteria)
 * @method static PaymentInstruction findOrCreate(array<string, mixed> $attributes)
 * @method static PaymentInstruction first(string $sortedField = 'id')
 * @method static PaymentInstruction last(string $sortedField = 'id')
 * @method static PaymentInstruction random(array<string, mixed> $attributes = [])
 * @method static PaymentInstruction randomOrCreate(array<string, mixed> $attributes = [])
 * @method static PaymentInstruction[] all()
 * @method static PaymentInstruction[] createMany(int $number, (array<string, mixed> | callable) $attributes = [])
 * @method static PaymentInstruction[] createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static PaymentInstruction[] findBy(array<string, mixed> $attributes)
 * @method static PaymentInstruction[] randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @method static PaymentInstruction[] randomSet(int $number, array<string, mixed> $attributes = [])
 * @method FactoryCollection<PaymentInstruction, PaymentInstructionFactory> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<PaymentInstruction, PaymentInstructionFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @method static RepositoryDecorator<PaymentInstruction, PaymentInstructionRepository> repository()
 *
 * @phpstan-method PaymentInstruction create(array<string, mixed>|callable $attributes = [])
 * @phpstan-method static PaymentInstruction createOne(array<string, mixed> $attributes = [])
 * @phpstan-method static PaymentInstruction find(object|array<string, mixed>|mixed $criteria)
 * @phpstan-method static PaymentInstruction findOrCreate(array<string, mixed> $attributes)
 * @phpstan-method static PaymentInstruction first(string $sortedField = 'id')
 * @phpstan-method static PaymentInstruction last(string $sortedField = 'id')
 * @phpstan-method static PaymentInstruction random(array<string, mixed> $attributes = [])
 * @phpstan-method static PaymentInstruction randomOrCreate(array<string, mixed> $attributes = [])
 * @phpstan-method static list<PaymentInstruction> all()
 * @phpstan-method static list<PaymentInstruction> createMany(int $number, array<string, mixed>|callable $attributes = [])
 * @phpstan-method static list<PaymentInstruction> createSequence(iterable<array<string, mixed>>|callable $sequence)
 * @phpstan-method static list<PaymentInstruction> findBy(array<string, mixed> $attributes)
 * @phpstan-method static list<PaymentInstruction> randomRange(int $min, int $max, array<string, mixed> $attributes = [])
 * @phpstan-method static list<PaymentInstruction> randomSet(int $number, array<string, mixed> $attributes = [])
 * @phpstan-method FactoryCollection<PaymentInstruction, PaymentInstructionFactory> many(int $min, int|null $max = null)
 * @phpstan-method FactoryCollection<PaymentInstruction, PaymentInstructionFactory> sequence(iterable<array<string, mixed>>|callable $sequence)
 * @extends PersistentObjectFactory<PaymentInstruction>
 */
final class PaymentInstructionFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'label' => self::faker()->randomElement(['Business account', 'EUR account', 'USD account']),
            'means' => PaymentMeans::BankTransfer,
            'accountName' => self::faker()->company(),
            'accountIdentifier' => strtoupper(self::faker()->bothify('??##################')),
            'default' => false,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return PaymentInstruction::class;
    }
}
