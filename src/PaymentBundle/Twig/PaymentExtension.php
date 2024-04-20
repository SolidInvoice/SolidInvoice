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

namespace SolidInvoice\PaymentBundle\Twig;

use Brick\Math\BigInteger;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaymentExtension extends AbstractExtension
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('payment_enabled', function (string $method): bool {
                $paymentMethod = $this
                    ->registry
                    ->getRepository(PaymentMethod::class)
                    ->findOneBy(['gatewayName' => $method]);

                if (null === $paymentMethod) {
                    return false;
                }

                return $paymentMethod->isEnabled();
            }),
            new TwigFunction('payments_configured', fn (bool $includeInternal = true): int => $this
                ->registry
                ->getRepository(PaymentMethod::class)
                ->getTotalMethodsConfigured($includeInternal)),
            new TwigFunction('total_income', fn (Client $client): BigInteger => $this
                ->registry
                ->getRepository(Payment::class)
                ->getTotalIncomeForClient($client)),
            new TwigFunction('total_outstanding', fn (Client $client): BigInteger => $this
                ->registry
                ->getRepository(Invoice::class)
                ->getTotalOutstandingForClient($client)),
        ];
    }
}
