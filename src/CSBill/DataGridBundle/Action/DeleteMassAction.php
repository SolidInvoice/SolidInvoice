<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Action;

class DeleteMassAction extends MassAction
{
    /**
     * @var string
     */
    protected $icon = 'ban';

    /**
     * @var string
     */
    protected $class = 'danger';

    /**
     * @param bool $confirm
     */
    public function __construct($confirm = true)
    {
        parent::__construct('Delete', 'static::deleteAction', $confirm);
    }
}
