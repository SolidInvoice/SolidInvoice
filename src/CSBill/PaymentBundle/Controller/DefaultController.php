<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Model\Status;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $source = new Entity('CSBillPaymentBundle:Payment');

        // Get a Grid instance
        $grid = $this->get('grid');
        $router = $this->get('router');
        $templating = $this->get('templating');
        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search) {
            if ($search) {
                $aliases = $queryBuilder->getRootAliases();

                $queryBuilder
                    ->orWhere($aliases[0].'.message LIKE :search')
                    ->orWhere($aliases[0].'.totalAmount LIKE :search')
                    ->orWhere($aliases[0].'.currencyCode LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $grid->getColumn('totalAmount')->setCurrencyCode($this->container->getParameter('currency'));
        $grid->getColumn('client.name')->manipulateRenderCell(function ($value, Row $row) use ($router) {
            $clientId = $row->getField('client.id');

            return '<a href="'.$router->generate('_clients_view', array('id' => $clientId)).'">'.$value.'</a>';
        })->setSafe(false);

        $grid->getColumn('invoice.id')->manipulateRenderCell(function ($value) use ($router) {
            return '<a href="'.$router->generate('_invoices_view', array('id' => $value)).'">'.$value.'</a>';
        })->setSafe(false);

        $grid->setDefaultOrder('created', 'DESC');

        return $grid->getGridResponse(
            'CSBillPaymentBundle:Default:list.html.twig',
            array(
                'status_list' => array(
                    Status::STATUS_UNKNOWN,
                    Status::STATUS_FAILED,
                    Status::STATUS_SUSPENDED,
                    Status::STATUS_EXPIRED,
                    Status::STATUS_PENDING,
                    Status::STATUS_CANCELLED,
                    Status::STATUS_NEW,
                    Status::STATUS_CAPTURED,
                    Status::STATUS_AUTHORIZED,
                    Status::STATUS_REFUNDED,
                ),
                'filters' => array()
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $paymentMethods = $this->get('payum')->getPaymentMethods();

        unset($paymentMethods['credit']);

        return $this->render(
            'CSBillPaymentBundle:Default:index.html.twig',
            array(
                'paymentMethods' => array_keys($paymentMethods),
            )
        );

    }
}
