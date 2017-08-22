<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MenuBundle;

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
