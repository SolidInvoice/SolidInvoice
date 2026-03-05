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

namespace SolidInvoice\ClientBundle\Search;

use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use Symfony\Component\Routing\RouterInterface;

final class ContactResultFormatter implements QualifiedResultFormatterInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function getSupportedQualifiers(): array
    {
        return [];
    }

    public function getIndexName(): string
    {
        return 'contacts';
    }

    public function format(array $hit): SearchResult
    {
        $name = trim(($hit['firstName'] ?? '') . ' ' . ($hit['lastName'] ?? ''));

        $url = isset($hit['clientId'])
            ? $this->router->generate('_clients_view', ['id' => $hit['clientId']])
            : $this->router->generate('_clients_index');

        return new SearchResult(
            type: 'contact',
            id: $hit['id'],
            title: $name !== '' ? $name : ($hit['email'] ?? ''),
            subtitle: $hit['email'] ?? '',
            url: $url,
        );
    }
}
