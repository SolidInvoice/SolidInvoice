<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Model\Status;
use CSBill\DataGridBundle\Grid\Filters;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Action\DeleteMassAction;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\QueryBuilder as QB;
use CSBill\ClientBundle\Form\Type\ClientType;

class DefaultController extends Controller
{
    /**
     * List all the clients
     *
     * @return Response
     */
    public function indexAction()
    {
        $source = new Entity('CSBillClientBundle:Client');

        // Get a Grid instance
        $grid = $this->get('grid');

        $request = $this->getRequest();

        // TODO : get better way of adding filters & search instead of defining it in the controller like this
        $filters = new Filters($this->getRequest());

        $filters->add('all_clients', null, true, array('active_class' => 'label label-info', 'default_class' => 'label'));

        $statusList = $this->getRepository('CSBillClientBundle:Status')->findAll();

        foreach ($statusList as $status) {
            $filters->add($status->getName().'_clients', function(QB $qb) use ($status) {
                $aliases = $qb->getRootAliases();
                $alias = $aliases[0];

                $qb->join($alias.'.status', 's')
                   ->andWhere('s.name = :status_name')
                   ->setParameter('status_name', $status->getName());
            }, false, array('active_class' => 'label label-' . $status->getLabel(), 'default_class' => 'label'));
        }

        $search = $this->getRequest()->get('search');

        $source->manipulateQuery(function(QB $qb) use ($search, $filters) {

            if ($filters->isFilterActive()) {
                $filter = $filters->getActiveFilter();
                $filter($qb);
            }

            if ($search) {
                $alias = $qb->getRootAlias();

                $qb->andWhere($alias.'.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        // Custom actions column in the wanted position
        $viewColumn = new ActionsColumn('info_column', $this->get('translator')->trans('Info'));
        $grid->addColumn($viewColumn, 100);

        $viewAction = new RowAction($this->get('translator')->trans('View'), '_clients_view');
        $viewAction->setColumn('info_column');
        $grid->addRowAction($viewAction);

        $editColumn = new ActionsColumn('edit_column', $this->get('translator')->trans('Edit'));
        $grid->addColumn($editColumn, 200);

        $editAction = new RowAction($this->get('translator')->trans('Edit'), '_clients_edit');
        $editAction->setColumn('edit_column');
        $grid->addRowAction($editAction);

        $grid->addMassAction(new DeleteMassAction());

        $grid->hideColumns(array('updated', 'deleted'));

        // Return the response of the grid to the template
        return $grid->getGridResponse('CSBillClientBundle:Default:index.html.twig', array('filters' => $filters));
    }

    /**
     * Adds a new client
     *
     * @return Response
     */
    public function addAction()
    {
        $client = new Client;

        $form = $this->createForm(new ClientType, $client);

        $request = $this->getRequest();

        if ($request->getMethod() === 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->get('doctrine.orm.entity_manager');

                $em->persist($client);
                $em->flush();

                $this->flash($this->trans('client_saved'), 'success');

                return $this->redirect($this->generateUrl('_clients_index'));
            }
        }

        return $this->render('CSBillClientBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * Edit a client
     *
     * @return Response
     */
    public function editAction(Client $client)
    {
        $form = $this->createForm(new ClientType, $client);

        return $this->render('CSBillClientBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * View a client
     *
     * @return Response
     */
    public function viewAction(Client $client)
    {
        return $this->render('CSBillClientBundle:Default:view.html.twig', array('client' => $client));
    }
}
