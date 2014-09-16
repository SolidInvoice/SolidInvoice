<?php

namespace CSBill\CoreBundle\Controller;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\CoreBundle\Entity\Tax;
use CSBill\CoreBundle\Form\Type\TaxType;
use CSBill\DataGridBundle\Grid\Filters;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TaxController extends BaseController
{
    public function ratesAction(Request $request)
    {
        $source = new Entity('CSBillCoreBundle:Tax');

        // Get a Grid instance
        $grid = $this->get('grid');
        $templating = $this->get('templating');

        $filters = new Filters($request);

        $filters->add(
            'all',
            null,
            true,
            array(
                'active_class' => 'label label-info',
                'default_class' => 'label label-default'
            )
        );

        $filters->add(
            'inclusive',
            function (QueryBuilder $queryBuilder) {
                $aliases = $queryBuilder->getRootAliases();
                $alias = $aliases[0];

                $queryBuilder
                    ->andWhere($alias . '.type = :type')
                    ->setParameter('type', 'inclusive');
            },
            false,
            array(
                'active_class' => 'label label-info',
                'default_class' => 'label label-default'
            )
        );

        $filters->add(
            'exclusive',
            function (QueryBuilder $queryBuilder) {
                $aliases = $queryBuilder->getRootAliases();
                $alias = $aliases[0];

                $queryBuilder
                    ->andWhere($alias . '.type = :type')
                    ->setParameter('type', 'exclusive');
            },
            false,
            array(
                'active_class' => 'label label-info',
                'default_class' => 'label label-default'
            )
        );

        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search, $filters) {
            if ($filters->isFilterActive()) {
                $filter = $filters->getActiveFilter();
                $filter($queryBuilder);
            }

            if ($search) {
                $aliases = $queryBuilder->getRootAliases();

                $queryBuilder
                    ->andWhere($aliases[0].'.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_edit_tax_rate');
        $editAction->addAttribute('title', $this->trans('Edit Tax Rate'));
        $editAction->addAttribute('rel', 'tooltip');

        $deleteIcon = $templating->render('{{ icon("times") }}');
        $deleteAction = new RowAction($deleteIcon, '_delete_tax_rate');
        $deleteAction->setAttributes(
            array(
                'title' => $this->trans('Delete Tax'),
                'rel' => 'tooltip',
                'data-confirm' => $this->trans('Are you sure you want to delete this tax method?'),
                'class' => 'delete-item',
            )
        );

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $deleteAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('deleted'));

        return $grid->getGridResponse('CSBillCoreBundle:tax:index.html.twig', array('filters' => $filters));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, Tax $tax = null)
    {
        $tax = $tax ?: new Tax();

        $form = $this->createForm(new TaxType(), $tax);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->save($tax);

            $this->flash($this->trans('Tax rate successfully saved'), 'success');

            return $this->redirect($this->generateUrl('_tax_rates'));
        }

        return $this->render('CSBillCoreBundle:Tax:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Tax $tax
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Tax $tax)
    {
        $entityMnager = $this->getEm();

        $this->getRepository('CSBillInvoiceBundle:Item')->removeTax($tax);
        $this->getRepository('CSBillQuoteBundle:Item')->removeTax($tax);
        $entityMnager->remove($tax);
        $entityMnager->flush();

        $this->flash($this->trans('Tax Deleted'), 'success');

        return new JsonResponse(array("status" => "success"));
    }

    /**
     * @param Tax $tax
     *
     * @return JsonResponse
     */
    public function getAction(Tax $tax)
    {
        $result = array(
            'id' => $tax->getId(),
            'name' => $tax->getName(),
            'type' => $tax->getType(),
            'rate' => $tax->getRate(),
        );

        return new JsonResponse($result);
    }
}
