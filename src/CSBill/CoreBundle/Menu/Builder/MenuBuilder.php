<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Menu\Builder;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

final class MenuBuilder extends ContainerAware
{
    /**
     *
     * @var BuilderInterface An instance of the class that creates a menu
     */
    protected $class;

    /**
     * @var string The name of the method to be called
     */
    protected $method;

    /**
     *
     * @param BuilderInterface $class
     * @param string           $method
     */
    public function __construct(BuilderInterface $class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Invokes the builder class to add items to the menu
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
