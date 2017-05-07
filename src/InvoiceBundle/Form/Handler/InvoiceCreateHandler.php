<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Form\Handler;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Money\Money;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class InvoiceCreateHandler implements FormHandlerInterface, FormHandlerResponseInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    /**
     * @var InvoiceManager
     */
    private $manager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(InvoiceManager $manager, PaymentRepository $paymentRepository, RouterInterface $router)
    {
        $this->manager = $manager;
        $this->router = $router;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options)
    {
        return $factory->create(InvoiceType::class, $options[0], $options[1] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@CSBillInvoice/Default/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($invoice, FormRequest $form): ?Response
    {
        /* @var Invoice $invoice */
        $action = $form->getRequest()->request->get('save');

        $invoice->setBalance($invoice->getTotal());

        $invoice = $this->manager->create($invoice);

        $totalPaid = $this->paymentRepository->getTotalPaidForInvoice($invoice);
        $invoice->setBalance($invoice->getTotal()->subtract(new Money($totalPaid, $invoice->getTotal()->getCurrency())));

        if ($action === Graph::STATUS_PENDING) {
            $this->manager->accept($invoice);
        }

        $this->save($invoice);

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

	return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'invoice.create.success';
            }
        };
    }
}
