<?php

namespace CSBill\QuoteBundle\Grid;

use CS\DataGridBundle\AbstractGrid;
use CS\DataGridBundle\Adapter\DoctrineEntity;

class DataGrid extends AbstractGrid
{
    public function getSource()
    {
        return new DoctrineEntity('CSBillQuoteBundle:Quote');
    }
}
