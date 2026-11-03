<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Symfony\Bridge\Twig\Attribute\Template;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
final readonly class ViewPayment
{
    public function __construct(
        private ClientRepository $clientRepository,
    ) {
    }

    /**
     * @return array{payment: Payment}
     */
    #[Template('@SolidInvoiceInvoice/Payment/view.html.twig')]
    public function __invoke(Payment $payment): array
    {
        $this->clientRepository->initializeArchived($payment->getClient());

        return ['payment' => $payment];
    }
}
