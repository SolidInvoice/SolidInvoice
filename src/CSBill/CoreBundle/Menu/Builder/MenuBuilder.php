<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu\Builder;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class MenuBuilder extends ContainerAware
{
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
    public function invoke(ItemInterface $menu, array $options = array())
    {
        if ($this->class instanceof ContainerAwareInterface) {
            $this->class->setContainer($this->container);
        }

        if ($this->class->validate()) {
            $this->class->{$this->method}($menu, $options);
        }
    }
}
