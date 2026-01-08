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

namespace SolidInvoice\PaymentBundle\Action;

use Brick\Math\Exception\MathException;
use DateMalformedStringException;
use SolidInvoice\PaymentBundle\Manager\PaymentStats;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private PaymentStats $paymentStats
    ) {
    }

    /**
     * @return array{stats: array<string, mixed>}
     * @throws MathException
     * @throws DateMalformedStringException
     */
    #[Template('@SolidInvoicePayment/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        return [
            'stats' => $this->paymentStats->getStatistics(),
        ];
    }
}
