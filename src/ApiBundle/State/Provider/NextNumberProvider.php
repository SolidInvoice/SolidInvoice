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

namespace SolidInvoice\ApiBundle\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use SolidInvoice\ApiBundle\ApiResource\NextNumber;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Templates\BillingDocumentType;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<NextNumber> */
final readonly class NextNumberProvider implements ProviderInterface
{
    public function __construct(
        private BillingIdGenerator $billingIdGenerator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): NextNumber
    {
        $type = BillingDocumentType::tryFrom((string) $context['request']?->query->get('type'))
            ?? throw new BadRequestHttpException(sprintf('The "type" query parameter must be one of: %s.', implode(', ', array_column(BillingDocumentType::cases(), 'value'))));

        // Every strategy only reads (MAX() of the existing numbers, or a fresh random/time
        // value), so generating here reserves nothing.
        $number = match ($type) {
            BillingDocumentType::Invoice => $this->billingIdGenerator->generate(new Invoice(), ['field' => 'invoiceId']),
            BillingDocumentType::Quote => $this->billingIdGenerator->generate(new Quote(), ['field' => 'quoteId']),
        };

        return new NextNumber($type, $number);
    }
}
