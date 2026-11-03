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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Manager\ManualPaymentManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<Payment, Payment>
 */
final readonly class VoidPaymentProcessor implements ProcessorInterface
{
    public function __construct(
        private ManualPaymentManager $paymentManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        // A POST does not 404 on its own when the item is missing (or another company's).
        if (! $data instanceof Payment) {
            throw new NotFoundHttpException('Payment not found.');
        }

        return $this->paymentManager->void($data);
    }
}
