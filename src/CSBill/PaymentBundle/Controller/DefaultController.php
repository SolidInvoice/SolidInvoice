<?php

namespace CSBill\PaymentBundle\Controller;

use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Form\PaymentMethodForm;
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

        $grid->getColumn('status.name')->manipulateRenderCell(function ($value, Row $row) {
            $label = $row->getField('status.label');

            return '<span class="label label-'.$label.'">'.ucfirst($value).'</span>';
        })->setSafe(false);

        $grid->getColumn('totalAmount')->setCurrencyCode($this->container->getParameter('currency'));
        $grid->getColumn('client.name')->manipulateRenderCell(function ($value, Row $row) use ($router) {
            $clientId = $row->getField('client.id');

            return '<a href="'.$router->generate('_clients_view', array('id' => $clientId)).'">'.$value.'</a>';
        })->setSafe(false);

        $grid->getColumn('invoice.id')->manipulateRenderCell(function ($value) use ($router) {
            return '<a href="'.$router->generate('_invoices_view', array('id' => $value)).'">'.$value.'</a>';
        })->setSafe(false);

        $grid->setDefaultOrder('created', 'DESC');

        $grid->hideColumns(array('updated', 'deletedAt'));

        return $grid->getGridResponse('CSBillPaymentBundle:Default:list.html.twig', array('filters' => array()));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $paymentMethods = $this->get('payum')->getPaymentMethods();

        return $this->render(
            'CSBillPaymentBundle:Default:index.html.twig',
            array(
                'paymentMethods' => array_keys($paymentMethods),
            )
        );

    }

    /**
     * @param Request       $request
     * @param PaymentMethod $payment
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, PaymentMethod $payment = null)
    {
        $paymentMethod = $payment ?: new PaymentMethod();

        $originalSettings = $paymentMethod->getSettings();

        $form = $this->createForm(new PaymentMethodForm(), $paymentMethod);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $settings = $paymentMethod->getSettings();
            foreach ($settings as $key => $value) {
                if ('password' === $key && null === $value && !empty($originalSettings[$key])) {
                    $settings[$key] = $originalSettings[$key];
                    $payment->setSettings($settings);
                    break;
                }
            }

            $this->save($paymentMethod);

            $this->flash($this->trans($payment ? 'payment_method_updated' : 'payment_method_added'), 'success');

            return $this->redirect($this->generateUrl('_payment_settings_index'));
        }

        return $this->render(
            'CSBillPaymentBundle:Default:add.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }
}
