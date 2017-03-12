<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MenuBundle\Builder;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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

    /**
     * @param BuilderInterface $class
     * @param string           $method
     */
    public function __construct(BuilderInterface $class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Invokes the builder class to add items to the menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
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
