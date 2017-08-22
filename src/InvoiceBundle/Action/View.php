<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;

final class View
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function __invoke(Request $request, Invoice $invoice)
    {
        return new Template(
            '@SolidInvoiceInvoice/Default/view.html.twig',
            [
                'invoice' => $invoice,
                'payments' => $this->paymentRepository->getPaymentsForInvoice($invoice),
            ]
        );
    }
}
