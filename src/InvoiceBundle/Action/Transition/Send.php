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

namespace CSBill\InvoiceBundle\Action\Transition;

use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class Send
{
    /**
     * @var InvoiceManager
     */
    private $manager;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(InvoiceManager $manager, Mailer $mailer, RouterInterface $router)
    {
        $this->manager = $manager;
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function __invoke(Request $request, Invoice $invoice)
    {
        if ($invoice->getStatus() !== Graph::STATUS_PENDING) {
            $this->manager->accept($invoice);
        } else {
            $this->mailer->sendInvoice($invoice);
        }

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

	return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.transition.action.sent';
            }
        };
    }
}
