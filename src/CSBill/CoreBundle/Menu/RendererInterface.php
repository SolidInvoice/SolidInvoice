<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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