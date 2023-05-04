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

namespace SolidInvoice\MenuBundle;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use SolidInvoice\MenuBundle\Builder\MenuBuilder;
use SplPriorityQueue;

/**
 * @see \SolidInvoice\MenuBundle\Tests\ProviderTest
 */
class Provider implements MenuProviderInterface
{
    /**
     * @var array<string, SplPriorityQueue<int, MenuBuilder>>
     */
    protected array $list = [];

    public function __construct(
        private readonly FactoryInterface $factory
    ) {
    }

    public function get(string $name, array $options = []): ItemInterface
    {
        $root = $this->factory->createItem('root');

        if (! $root instanceof MenuItem) {
            dd($root);
        }

        assert($root instanceof MenuItem);

        foreach ($this->list[$name] as $builder) {
            $builder->invoke($root, $options);
        }

        return $root;
    }

    public function has(string $name, array $options = []): bool
    {
        return $this->hasBuilder($name);
    }

    /**
     * Adds a builder to the storage.
     *
     * @param string $name   The name of the menu the builder should be attached to
     * @param string $method The method to call to build the menu
     */
    public function addBuilder(BuilderInterface $class, string $name, string $method, int $priority): void
    {
        $builder = new MenuBuilder($class, $method);

        if (! $this->hasBuilder($name)) {
            $this->list[$name] = new SplPriorityQueue();
        }

        $this->list[$name]->insert($builder, $priority);
    }

    private function hasBuilder(string $name): bool
    {
        return isset($this->list[$name]);
    }
}
