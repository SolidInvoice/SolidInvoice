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

namespace SolidInvoice\ApiBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use SolidInvoice\ApiBundle\State\Provider\NextNumberProvider;
use SolidInvoice\CoreBundle\Templates\BillingDocumentType;

/**
 * The number the next invoice or quote created without one would get. A preview: nothing
 * is reserved, so a create in between can take it.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/next-number',
            uriVariables: [],
            provider: NextNumberProvider::class,
            parameters: [
                'type' => new QueryParameter(
                    schema: ['type' => 'string', 'enum' => ['invoice', 'quote']],
                    description: 'The document type to preview the next number for.',
                    required: true,
                ),
            ],
        ),
    ],
)]
final readonly class NextNumber
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public BillingDocumentType $type,
        public string $number,
    ) {
    }
}
