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
use CSBill\DataGridBundle\Grid\Filters;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Action\RowAction;
use Doctrine\ORM\QueryBuilder;
use CSBill\ClientBundle\Form\Client as ClientForm;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * List all the clients
     *
     * @param  Request                                    $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('CSBillClientBundle:Client');

        // Get a Grid instance
        $grid = $this->get('grid');

        // TODO : get better way of adding filters & search instead of defining it in the controller like this
        $filters = new Filters($request);

        $filters->add('all_clients', null, true, array('active_class' => 'label label-info', 'default_class' => 'label label-default'));

        $statusList = $this->getRepository('CSBillClientBundle:Status')->findAll();

        foreach ($statusList as $status) {
            $filters->add($status->getName().'_clients', function (QueryBuilder $queryBuilder) use ($status) {
                $aliases = $queryBuilder->getRootAliases();
                $alias = $aliases[0];

                $queryBuilder->join($alias.'.status', 's')
                   ->andWhere('s.name = :status_name')
                   ->setParameter('status_name', $status->getName());
            }, false, array('active_class' => 'label label-' . $status->getLabel(), 'default_class' => 'label label-default'));
        }

        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search, $filters) {

            if ($filters->isFilterActive()) {
                $filter = $filters->getActiveFilter();
                $filter($queryBuilder);
            }

            if ($search) {
                $aliases = $queryBuilder->getRootAliases();

                $queryBuilder->andWhere($aliases[0].'.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $viewAction = new RowAction($this->get('translator')->trans('view'), '_clients_view');
        //$viewAction->setAttributes(array('class' => 'btn'));

        $editAction = new RowAction($this->get('translator')->trans('edit'), '_clients_edit');
        //$editAction->setAttributes(array('class' => 'btn'));

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deleted'));

        // Return the response of the grid to the template
        return $grid->getGridResponse('CSBillClientBundle:Default:index.html.twig', array('filters' => $filters));
    }

    /**
     * Adds a new client
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction()
    {
        $client = new Client;

        // set all new clients default to active
        $client->setStatus($this->getRepository('CSBillClientBundle:Status')->findOneByName('active'));

        $form = $this->createForm(new ClientForm, $client);

        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getEm();

            $entityManager->persist($client);
            $entityManager->flush();

            $this->flash($this->trans('client_saved'), 'success');

            return $this->redirect($this->generateUrl('_clients_view', array('id' => $client->getId())));
        }

        return $this->render('CSBillClientBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * Edit a client
     *
     * @param  Client                                     $client
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Client $client)
    {
        $form = $this->createForm(new ClientForm, $client);

        $request = $this->getRequest();

        $originalContactsDetails = array();
        $originalContacts = array();

        if ($request->isMethod('POST')) {
            $originalContacts = $client->getContacts()->toArray();

            foreach ($originalContacts as $contact) {
                /** @var \CSBill\ClientBundle\Entity\Contact $contact */
                $originalContactsDetails[$contact->getId()] = $contact->getDetails()->toArray();
                $contact->getDetails()->clear();
            }
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getEm();

            foreach ($client->getContacts() as $originalContact) {
                foreach ($originalContacts as $key => $toDel) {
                    if ($toDel->getId() === $originalContact->getId()) {
                        unset($originalContacts[$key]);
                    }
                }
            }

            unset($contact);

            foreach ($originalContacts as $contact) {
                $entityManager->remove($contact);
                $client->removeContact($contact);
            }

            unset($contact);

            foreach ($client->getContacts() as $contact) {
                foreach ($contact->getDetails() as $originalContactDetail) {
                    foreach ($originalContactsDetails[$contact->getId()] as $key => $toDel) {
                        if ($toDel->getId() === $originalContactDetail->getId()) {
                            unset($originalContactsDetails[$contact->getId()][$key]);
                        }
                    }
                }

                foreach ($originalContactsDetails[$contact->getId()] as $contactDetail) {
                    $entityManager->remove($contactDetail);
                    $contact->removeDetail($contactDetail);
                }
            }

            $entityManager->persist($client);
            $entityManager->flush();

            $this->flash($this->trans('client_saved'), 'success');

            return $this->redirect($this->generateUrl('_clients_view', array('id' => $client->getId())));
        }

        return $this->render('CSBillClientBundle:Default:edit.html.twig', array('client' => $client, 'form' => $form->createView()));
    }

    /**
     * View a client
     *
     * @param  Client                                     $client
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Client $client)
    {
        return $this->render('CSBillClientBundle:Default:view.html.twig', array('client' => $client));
    }
}
