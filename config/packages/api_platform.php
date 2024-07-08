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

use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use Symfony\Config\ApiPlatformConfig;

return static function (ApiPlatformConfig $config): void {
    $config
        ->title(SolidInvoiceCoreBundle::APP_NAME)
        ->version(SolidInvoiceCoreBundle::VERSION)
        ->showWebby(false)
        ->metadataBackwardCompatibilityLayer(false)
    ;

    $config
        ->formats('jsonld')
        ->mimeTypes(['application/ld+json']);

    $config
        ->formats('jsonhal')
        ->mimeTypes(['application/hal+json']);

    $config
        ->formats('json')
        ->mimeTypes(['application/json']);

    $config
        ->formats('xml')
        ->mimeTypes(['application/xml', 'text/xml']);

    $config
        ->formats('html')
        ->mimeTypes(['text/html']);

    $config->swagger()
        ->versions([2, 3])
        ->apiKeys()
        ->name('X-API-TOKEN')
        ->type('header')->toArray();

    $config->defaults()
        ->formats(['json']);

    $config->description(
        <<<'DESC'
SolidInvoice is a simple open source invoicing application aimed to help small businesses and freelancers manage their day-to-day billing.

### Authentication
SolidInvoice uses an API tokens for authentication.
To authenticate, you need to create an API token and set the `X-API-TOKEN` header to your API token.

To create an API token, go to the [API Tokens](/profile/api) page and click the `Create Token` button.

```bash
curl -H "X-API-TOKEN=${apiToken}" https://example.com/api/invoices
```

Or when using a PHP client:

```php
$client = new \GuzzleHttp\Client();
$client->request(\'GET\', \'https://example.com/api/invoices\', [
    'headers' => [
        'X-API-TOKEN' => '${apiToken}',
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

- `jsonld`: `application/ld+json`
- `jsonhal`: `application/hal+json`
- `json`: `application/json`
- `xml`: `application/xml`, `text/xml`
- `html`: `text/html`

Example changing the format:

```bash
curl -H "Accept: application/hal+json" https://example.com/api/invoices
```

### Swagger Versions

The API supports the following Swagger versions:

- `2`
- `3`
DESC
    );
};
