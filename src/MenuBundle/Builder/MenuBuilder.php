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

namespace SolidInvoice\MenuBundle\Builder;

use SolidInvoice\MenuBundle\MenuItem;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @see \SolidInvoice\MenuBundle\Tests\Builder\MenuBuilderTest
 */
final class MenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(protected BuilderInterface $class, protected string $method)
    {
    }

    /**
     * Invokes the builder class to add items to the menu.
     *
     * @param array<string, mixed> $options
     */
    public function invoke(MenuItem $menu, array $options = []): void
    {
        if ($this->class->validate()) {
            $this->class->{$this->method}($menu, $options);
        }
    }
}
