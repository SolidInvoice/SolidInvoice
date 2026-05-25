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

namespace SolidInvoice\CoreBundle\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use function is_array;
use function str_replace;
use function strtoupper;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class CustomFieldsSchemaDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $names = [
            'Client', 'Contact', 'Invoice', 'Quote', 'RecurringInvoice',
            'Client.jsonld', 'Contact.jsonld', 'Invoice.jsonld', 'Quote.jsonld', 'RecurringInvoice.jsonld',
        ];
        foreach ($names as $name) {
            if (! isset($schemas[$name])) {
                continue;
            }

            $schema = $schemas[$name];
            if (! is_array($schema)) {
                continue;
            }

            $base = str_replace('.jsonld', '', $name);
            $entity = strtoupper($base === 'RecurringInvoice' ? 'INVOICE' : $base);
            $properties = $schema['properties'] ?? [];
            $properties['customFields'] = [
                'type' => 'object',
                'description' => 'User-defined custom field values keyed by field_key. Discover available keys via GET /api/custom-fields?target=' . $entity . '.',
                'additionalProperties' => true,
                'example' => ['department' => 'Sales', 'tier' => 'gold'],
            ];
            $schema['properties'] = $properties;
            $schemas[$name] = $schema;
        }

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
