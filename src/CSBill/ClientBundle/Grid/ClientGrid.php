<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\ClientBundle\Entity\Status;
use CSBill\DataGridBundle\Action\ActionColumn;
use CSBill\DataGridBundle\Action\Collection;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\DataGridBundle\Action\MassAction;
use CSBill\DataGridBundle\Grid\Filters;
use CSBill\DataGridBundle\GridInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\QueryBuilder;

class ClientGrid implements GridInterface
{
    /**
     * @var ObjectRepository
     */
    private $statusRepository;

    /**
     * @param ObjectRepository $statusRepository
     */
    public function __construct(ObjectRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return new Entity('CSBillClientBundle:Client');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(Filters $filters)
    {
        /** @var Status[] $statusList */
        $statusList = $this->statusRepository->findAll();

        foreach ($statusList as $status) {
            $filters->add(
                $status->getName() . '_clients',
                function (QueryBuilder $queryBuilder) use ($status) {
                    $aliases = $queryBuilder->getRootAliases();
                    $alias = $aliases[0];

                    $queryBuilder->join($alias . '.status', 's')
                        ->andWhere('s.name = :status_name')
                        ->setParameter('status_name', $status->getName());
                },
                false,
                array(
                    'active_class' => 'label label-' . $status->getLabel(),
                )
            );
        }

        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
        $aliases = $queryBuilder->getRootAliases();

        $queryBuilder->andWhere($aliases[0] . '.name LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getRowActions(Collection $collection)
    {
        $viewAction = new ActionColumn();
        $viewAction->setIcon('eye')
            ->setTitle('client.grid.actions.view')
            ->setRoute('_clients_view')
            ->setClass('primary')
        ;

        $editAction = new ActionColumn();
        $editAction->setIcon('edit')
            ->setTitle('client.grid.actions.edit')
            ->setRoute('_clients_edit')
            ->setClass('info')
        ;

        $deleteAction = new ActionColumn();
        $deleteAction->setIcon('times')
            ->setTitle('client.grid.actions.delete')
            ->setRoute('_clients_delete')
            ->setConfirm('confirm_delete')
            ->setAttributes(array('class' => 'delete-client'))
            ->setClass('danger')
        ;

        $collection->add($viewAction);
        $collection->add($editAction);
        $collection->add($deleteAction);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        $archive = new MassAction('Archive', function() {
            var_dump(func_get_args());
            exit;
        }, true);

        $archive->setIcon('archive');
        $archive->setClass('warning');

        $deleteAction = new DeleteMassAction(true);

        return array(
            $archive,
            $deleteAction
        );
    }

    /**
     * {@inheritdoc}
     */
    /*public function getColumns()
    {
        $viewIcon = $templating->render('{{ icon("eye") }}');
        $viewAction = new RowAction($viewIcon, '_clients_view');
        $viewAction->addAttribute('title', $translator->trans('view_client'));
        $viewAction->addAttribute('rel', 'tooltip');

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_clients_edit');
        $editAction->addAttribute('title', $translator->trans('edit_client'));
        $editAction->addAttribute('rel', 'tooltip');

        $deleteIcon = $templating->render('{{ icon("times") }}');
        $deleteAction = new RowAction($deleteIcon, '_clients_delete');
        $deleteAction->setAttributes(
            array(
                'title' => $translator->trans('delete_client'),
                'rel' => 'tooltip',
                'data-confirm' => $translator->trans('confirm_delete'),
                'class' => 'delete-client',
            )
        );

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction, $deleteAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deletedAt'));

        $grid->getColumn('website')->manipulateRenderCell(
            function ($value) {
                if (!empty($value)) {
                    return '<a href="' . $value . '" target="_blank">' . $value . '<a>';
                }

                return $value;
            }
        )->setSafe(false);

    }*/

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillClientBundle:Default:index.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return true;
    }
}