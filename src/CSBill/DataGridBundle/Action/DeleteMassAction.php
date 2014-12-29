<?php

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