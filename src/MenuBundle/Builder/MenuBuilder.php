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

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @see \SolidInvoice\MenuBundle\Tests\Builder\MenuBuilderTest
 */
final class MenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var BuilderInterface An instance of the class that creates a menu
     */
    protected $class;

    /**
     * @var string The name of the method to be called
     */
    protected $method;

    public function __construct(BuilderInterface $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Invokes the builder class to add items to the menu.
     *
     * @return mixed
     */
    public function invoke(ItemInterface $menu, array $options = [])
    {
        if ($this->class instanceof ContainerAwareInterface) {
            $this->class->setContainer($this->container);
        }

        if ($this->class->validate()) {
            $this->class->{$this->method}($menu, $options);
        }
    }
}
