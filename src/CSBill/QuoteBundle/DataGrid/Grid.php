<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CS\QuoteBundle\DataGrid;

use CS\DataGridBundle\Grid\BaseGrid;
use CS\DataGridBundle\Grid\Column\ColumnCollection;
use CS\DataGridBundle\Grid\Action\ActionCollection;
use CS\DataGridBundle\Grid\Action\Action;

class Grid extends BaseGrid
{
    /**
     * returns the entity name for the cliets
     *
     * @see CS\DataGridBundle\Grid.BaseGrid::getSource()
     * @return string
     */
    public function getSource()
    {
        return 'CSQuoteBundle:Quote';
    }

    /**
     * @return string The name of the current grid
     */
    public function getName()
    {
        return 'quotes';
    }

    /**
     * Manupulate the columns for the clients grid
     *
     * @param  ColumnCollection $collection
     * @return void
     */
    public function getColumns(ColumnCollection $collection)
    {
        $collection->remove(array('deleted', 'updated'));

        $collection['id']->setLabel('#');

        $collection->move('client', 5);
        $collection->move('created', 10);
        $collection->move('due', 15);
        $collection->move('total', 20);
        $collection->move('status', 25);

        $collection['client']->setCallback(function($row) {
        	return '<a href="#">'.$row->getClient().'</a>';
        });

        $collection['total']->setLabel('Amount');

        $collection['status']->setCallback(function($row) {

        	$status = (string) $row->getStatus();

        	$label = '';

        	switch(strtolower($status))
        	{
        		case "pending":
        			$label = "info";
        		break;
        	}
			return '<span class="label label-'.$label.'">'.$status.'</span>';
        });

        $collection->add("items", 30, function($row) {
        	$content = '';

        	foreach($row->getItems() as $item)
        	{
        		$content .= "<div>".$item->getName()."</div>";
        	}

        	return $content;
        });
    }

    /**
     * Adds the default CRUD actions for clients
     *
     * @param  ActionCollection $actions
     * @return void
     */
    public function getActions(ActionCollection $actions)
    {
        $add = new Action('Create New Quote');

        $add->setAction('_quote_add')
            ->attributes(array('class' => 'btn btn-success'));

        $actions->add($add);
    }
}
