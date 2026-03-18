<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Test\Factory;

use SolidInvoice\ApiBundle\Entity\Webhook;
use SolidInvoice\ApiBundle\Repository\WebhookRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method Webhook|Proxy<Webhook> create((array | callable) $attributes = [])
 * @method static Webhook|Proxy<Webhook> createOne(array $attributes = [])
 * @method static Webhook|Proxy<Webhook> find((object | array | mixed) $criteria)
 * @method static Webhook|Proxy<Webhook> findOrCreate(array $attributes)
 * @method static Webhook|Proxy<Webhook> first(string $sortedField = 'id')
 * @method static Webhook|Proxy<Webhook> last(string $sortedField = 'id')
 * @method static Webhook|Proxy<Webhook> random(array $attributes = [])
 * @method static Webhook|Proxy<Webhook> randomOrCreate(array $attributes = [])
 * @method static Webhook[]|Proxy<Webhook>[] all()
 * @method static Webhook[]|Proxy<Webhook>[] createMany(int $number, (array | callable) $attributes = [])
 * @method static Webhook[]|Proxy<Webhook>[] createSequence((iterable | callable) $sequence)
 * @method static Webhook[]|Proxy<Webhook>[] findBy(array $attributes)
 * @method static Webhook[]|Proxy<Webhook>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Webhook[]|Proxy<Webhook>[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<(Webhook | Proxy<Webhook>)> many(int $min, (int | null) $max = null)
 * @method FactoryCollection<(Webhook | Proxy<Webhook>)> sequence((iterable | callable) $sequence)
 * @method static ProxyRepositoryDecorator<Webhook, WebhookRepository> repository()
 *
 * @phpstan-method Webhook&Proxy<Webhook> create((array | callable) $attributes = [])
 * @phpstan-method static Webhook&Proxy<Webhook> createOne(array $attributes = [])
 * @phpstan-method static Webhook&Proxy<Webhook> find((object | array | mixed) $criteria)
 * @phpstan-method static Webhook&Proxy<Webhook> findOrCreate(array $attributes)
 * @phpstan-method static Webhook&Proxy<Webhook> first(string $sortedField = 'id')
 * @phpstan-method static Webhook&Proxy<Webhook> last(string $sortedField = 'id')
 * @phpstan-method static Webhook&Proxy<Webhook> random(array $attributes = [])
 * @phpstan-method static Webhook&Proxy<Webhook> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Webhook&Proxy<Webhook>> all()
 * @phpstan-method static list<Webhook&Proxy<Webhook>> createMany(int $number, (array | callable) $attributes = [])
 * @phpstan-method static list<Webhook&Proxy<Webhook>> createSequence((iterable | callable) $sequence)
 * @phpstan-method static list<Webhook&Proxy<Webhook>> findBy(array $attributes)
 * @phpstan-method static list<Webhook&Proxy<Webhook>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Webhook&Proxy<Webhook>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<Webhook&Proxy<Webhook>> many(int $min, (int | null) $max = null)
 * @phpstan-method FactoryCollection<Webhook&Proxy<Webhook>> sequence((iterable | callable) $sequence)
 * @extends PersistentProxyObjectFactory<Webhook>
 */
final class WebhookFactory extends PersistentProxyObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'url' => 'https://example.com/webhook',
            'events' => self::faker()->randomElements(Webhook::SUPPORTED_EVENTS, self::faker()->numberBetween(1, count(Webhook::SUPPORTED_EVENTS))),
            'active' => true,
            'company' => CompanyFactory::random(),
        ];
    }

    public static function class(): string
    {
        return Webhook::class;
    }
}
