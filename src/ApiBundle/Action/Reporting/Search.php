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

namespace SolidInvoice\ApiBundle\Action\Reporting;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Search\DatabaseSearch;
use SolidInvoice\CoreBundle\Search\MultiSearchService;
use SolidInvoice\CoreBundle\Search\ParsedQuery;
use SolidInvoice\CoreBundle\Search\SearchQueryParser;
use SolidInvoice\CoreBundle\Search\SearchResult;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;
use function array_filter;
use function array_intersect;
use function array_map;
use function array_values;
use function explode;
use function mb_strlen;
use function trim;

/**
 * Clients, contacts, invoices and quotes matching `q`, at most 10 of each, grouped by type. Takes the
 * same qualifiers as the search box in the web app (`status:overdue`, `client:smith`). Meilisearch
 * when it is configured, a database search otherwise.
 */
#[AsController]
#[Route(path: '/search', name: 'api_search', methods: ['GET'])]
final readonly class Search
{
    private const int HITS = 10;

    public function __construct(
        private SearchQueryParser $parser,
        private MultiSearchService $meilisearch,
        private DatabaseSearch $database,
        private ToggleInterface $toggle,
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            throw new BadRequestHttpException('q must be at least 2 characters.');
        }

        $types = array_values(array_intersect(
            DatabaseSearch::INDICES,
            array_filter(array_map(trim(...), explode(',', (string) $request->query->get('types', 'clients,contacts,invoices,quotes')))),
        ));

        $parsed = $this->parser->parse($q);
        // The `in:` qualifier narrows the types further; it cannot widen them.
        $indices = $parsed->indices === [] ? $types : array_values(array_intersect($parsed->indices, $types));
        $query = new ParsedQuery($parsed->fulltext, $indices, $parsed->indexFilters, $parsed->sort, self::HITS);

        $results = $indices === [] ? [] : ($this->toggle->isActive('meilisearch_search') ? $this->meilisearch->search($query) : $this->database->search($query));

        $grouped = [];

        foreach ($indices as $index) {
            $grouped[$index] = array_map($this->item(...), $results[$index] ?? []);
        }

        $response = new JsonResponse($grouped);
        $response->setPrivate();

        return $response;
    }

    /**
     * @return array<string, string|null>
     */
    private function item(SearchResult $result): array
    {
        $iri = match ($result->type) {
            'client' => $this->iri(Client::class, $result->id),
            'invoice' => $this->iri(Invoice::class, $result->id),
            'quote' => $this->iri(Quote::class, $result->id),
            // A contact opens its client.
            default => $result->clientId === null ? null : $this->iri(Client::class, $result->clientId),
        };

        return [
            'type' => $result->type,
            'iri' => $iri,
            'title' => $result->title,
            'subtitle' => $result->subtitle !== '' ? $result->subtitle : null,
            'status' => $result->status,
            'detail' => $result->meta,
        ];
    }

    /**
     * @param class-string $class
     */
    private function iri(string $class, string $id): string
    {
        // Meilisearch hits may carry the id in another encoding.
        return $this->iriConverter->getIriFromResource($class, UrlGeneratorInterface::ABS_PATH, context: ['uri_variables' => ['id' => Ulid::fromString($id)->toBase32()]]);
    }
}
