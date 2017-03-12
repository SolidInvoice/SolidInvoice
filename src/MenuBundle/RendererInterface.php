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

namespace CSBill\MenuBundle;

use Knp\Menu\Renderer\RendererInterface as BaseInterface;

interface RendererInterface extends BaseInterface
{
    /**
     * Build and render a menu.
     *
     * @param \SplPriorityQueue $storage
     * @param array             $options
     *
     * @return mixed
     */
    public function build(\SplPriorityQueue $storage, array $options = []);
}
