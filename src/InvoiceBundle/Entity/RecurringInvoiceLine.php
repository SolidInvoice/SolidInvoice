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

namespace SolidInvoice\InvoiceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ApiBundle\State\Processor\RecurringInvoiceLinePersistProcessor;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceLineRepository;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ORM\Entity(repositoryClass: RecurringInvoiceLineRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/recurring-invoices/{invoiceId}/lines',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: RecurringInvoice::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/recurring-invoices/{invoiceId}/lines',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: RecurringInvoice::class,
                ),
            ],
            processor: RecurringInvoiceLinePersistProcessor::class,
        ),
        new Get(
            uriTemplate: '/recurring-invoices/{invoiceId}/line/{id}',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: RecurringInvoice::class,
                ),
                'id' => new Link(
                    fromClass: RecurringInvoiceLine::class,
                ),
            ],
        ),
        new Patch(
            uriTemplate: '/recurring-invoices/{invoiceId}/line/{id}',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: RecurringInvoice::class,
                ),
                'id' => new Link(
                    fromClass: RecurringInvoiceLine::class,
                ),
            ],
        ),
        new Delete(
            uriTemplate: '/recurring-invoices/{invoiceId}/line/{id}',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'lines',
                    fromClass: RecurringInvoice::class,
                ),
                'id' => new Link(
                    fromClass: RecurringInvoiceLine::class,
                ),
            ],
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
class RecurringInvoiceLine extends Line
{
    #[ORM\ManyToOne(targetEntity: RecurringInvoice::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?RecurringInvoice $recurringInvoice = null;

    public function setRecurringInvoice(?RecurringInvoice $invoice): self
    {
        $this->recurringInvoice = $invoice;

        return $this;
    }

    public function getRecurringInvoice(): ?RecurringInvoice
    {
        return $this->recurringInvoice;
    }
}
