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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;

$formats = [
    'jsonld' => ['mime_types' => ['application/ld+json']],
    'json' => ['mime_types' => ['application/json']],
    'hal' => ['mime_types' => ['application/hal+json']],
    'jsonapi' => ['mime_types' => ['application/vnd.api+json']],
    'xml' => ['mime_types' => ['application/xml', 'text/xml']],
    'html' => ['mime_types' => ['text/html']],
    'multipart' => ['mime_types' => ['multipart/form-data']],
];

$swaggerVersions = [3];

$versions = implode("\n* ", $swaggerVersions);

$formatDesc = '';

foreach ($formats as $format => $formatConfig) {
    if ($format === 'html') {
        continue;
    }

    $formatDesc .= sprintf('- `%s`: `', $format) . implode('`, `', $formatConfig['mime_types']) . "`\n";
}

return App::config([
    'api_platform' => [
        'title' => SolidInvoiceCoreBundle::APP_NAME,
        'version' => SolidInvoiceCoreBundle::VERSION,
        'show_webby' => false,
        'enable_profiler' => param('kernel.debug'),
        'path_segment_name_generator' => 'api_platform.metadata.path_segment_name_generator.dash',
        'formats' => $formats,
        'docs_formats' => [
            'json' => ['mime_types' => ['application/json']],
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonopenapi' => ['mime_types' => ['application/vnd.openapi+json']],
            'html' => ['mime_types' => ['text/html']],
            'xml' => ['mime_types' => ['application/xml', 'text/xml']],
        ],
        'patch_formats' => [
            'json' => ['mime_types' => ['application/merge-patch+json', 'application/json']],
            'jsonapi' => ['mime_types' => ['application/vnd.api+json']],
        ],
        'error_formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']], // Hydra error formats
            'jsonproblem' => ['mime_types' => ['application/problem+json']],
            'jsonapi' => ['mime_types' => ['application/vnd.api+json']],
        ],
        'swagger' => [
            'versions' => $swaggerVersions,
            'swagger_ui_extra_configuration' => [
                'filter' => true,
                'docExpansion' => 'none',
                'defaultModelsExpandDepth' => 0,
                'persistAuthorization' => true,
                'tagsSorter' => 'alpha',
            ],
            'api_keys' => [
                'bearer' => [
                    'name' => 'X-API-TOKEN',
                    'type' => 'header',
                ],
            ],
        ],
        'defaults' => [
            'stateless' => true,
            'extra_properties' => ['standard_put' => true, 'rfc_7807_compliant_errors' => true],
            'cache_headers' => [['Content-Type', 'Authorization', 'Origin']],
        ],
        'use_symfony_listeners' => true,
        'graphql' => [
            'enabled' => true,
            'graphiql' => ['enabled' => true],
        ],
        'description' => <<<DESC
SolidInvoice is a simple open source invoicing application aimed to help small businesses and freelancers manage their day-to-day billing.

### Authentication
SolidInvoice uses an API tokens for authentication.
To authenticate, you need to create an API token and set the `X-API-TOKEN` header to your API token.

To create an API token, go to the [API Tokens](/profile/api) page and click the `Create Token` button.

```bash
curl -H "X-API-TOKEN=\${apiToken}" https://example.com/api/invoices
```

Or when using a PHP client:

```php
\$client = new \GuzzleHttp\Client();
\$client->request('GET', 'https://example.com/api/invoices', [
    'headers' => [
        'X-API-TOKEN' => \$apiToken,
    ],
]);
```

### API Documentation

The API documentation is available at `/api/docs`.
You can also view the documentation in other formats by going to [`/api/docs.json`](/api/docs.json), [`/api/docs.jsonld`](/api/docs.jsonld) or [`/api/docs.xml`](/api/docs.xml).

### Pagination

The default page size is 30. You can change the page size by setting the `itemsPerPage` query parameter. You can also change the page by setting the `page` query parameter.

```bash
GET /api/invoices?itemsPerPage=10&page=2
```

### Error Handling

The API uses the [RFC7807](https://tools.ietf.org/html/rfc7807) standard for error handling. If an error occurs, the API will return a JSON object with the following properties:

- `type`: A reference that identifies the problem type.
- `title`: A short, human-readable summary of the problem type. It SHOULD NOT change from occurrence to occurrence of the problem, except for purposes of localization (e.g., using proactive content negotiation; see [RFC7231], Section 3.4).
- `description`: A human-readable explanation specific to this occurrence of the problem

### Formats

The API supports the following formats, based on the `Accept` header:

{$formatDesc}

Example changing the format:

```bash
curl -H "Accept: application/hal+json" https://example.com/api/invoices
```

### Swagger Versions

The API supports the following Swagger versions:

* {$versions}

### Rate Limiting

The API enforces a rate limit of **300 requests per minute** per API token (or IP address for unauthenticated requests).
When the rate limit is exceeded, the API returns a `429 Too Many Requests` response with a `Retry-After` header indicating when to retry.

### Versioning Policy

SolidInvoice does not use URL-based versioning (e.g., `/api/v1`). Breaking changes follow a **6-month sunset cycle**.
Deprecated operations return a `Sunset` header indicating when they will be removed.

### Integration Guides

SolidInvoice's API is compatible with popular automation platforms:

- **Make.com** (formerly Integromat): Use the HTTP module with `X-API-TOKEN` header authentication.
- **n8n**: Use the HTTP Request node with Header Auth. Set header name `X-API-TOKEN` and your token as the value.
- **Zapier**: Use the Webhooks by Zapier action with custom headers.

All monetary amounts are represented as **integers in the smallest currency unit** (e.g., cents for USD/EUR).
For example, `1000` represents `\$10.00`.
DESC,
    ],
]);
