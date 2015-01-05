<?php
/**
 * This file is part of the MiWay Business Insurance project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\Renderer\RendererInterface as BaseInterface;

interface RendererInterface extends BaseInterface
{
    /**
     * Build and render a menu
     *
     * @param \SplObjectStorage $storage
     * @param array             $options
     *
     * @return mixed
     */
    public function build(\SplObjectStorage $storage, array $options = array());
}