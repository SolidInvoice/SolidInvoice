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

namespace SolidInvoice\CoreBundle\Search;

use Doctrine\ORM\QueryBuilder;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Repository\ContactRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use function addcslashes;
use function array_map;
use function mb_strtolower;
use function preg_match;
use function trim;

/**
 * Search without Meilisearch: a case-insensitive substring match on names, numbers and contact
 * details, through the CompanyFilter. Returns the same shape as {@see MultiSearchService}, and
 * understands the `status:` and `client:` qualifiers.
 */
final readonly class DatabaseSearch
{
    public const array INDICES = ['clients', 'contacts', 'invoices', 'quotes'];

    public function __construct(
        private ClientRepository $clientRepository,
        private ContactRepository $contactRepository,
        private InvoiceRepository $invoiceRepository,
        private QuoteRepository $quoteRepository,
    ) {
    }

    /**
     * @return array<string, list<SearchResult>> keyed by index name
     */
    public function search(ParsedQuery $query): array
    {
        $term = '%' . addcslashes(mb_strtolower(trim($query->fulltext)), '%_\\') . '%';
        $indices = $query->indices !== [] ? $query->indices : self::INDICES;
        $results = [];

        foreach ($indices as $index) {
            $status = $this->qualifier($query, $index, 'status');
            $client = $this->qualifier($query, $index, 'client\.name');

            $found = match ($index) {
                'clients' => array_map(
                    static fn (Client $c): SearchResult => new SearchResult('client', (string) $c->getId(), (string) $c->getName(), (string) $c->getWebsite(), '', $c->getStatus()?->value),
                    $this->filtered($this->clientRepository->createQueryBuilder('c')->where('LOWER(c.name) LIKE :term'), $query, $term, $status, null, 'c')->getQuery()->getResult(),
                ),
                'contacts' => array_map(
                    static fn (Contact $c): SearchResult => new SearchResult('contact', (string) $c->getId(), trim($c->getFirstName() . ' ' . $c->getLastName()), (string) $c->getEmail(), '', clientId: (string) $c->getClient()?->getId()),
                    $this->filtered($this->contactRepository->createQueryBuilder('ct')->where("LOWER(CONCAT(ct.firstName, ' ', COALESCE(ct.lastName, ''))) LIKE :term OR LOWER(ct.email) LIKE :term OR ct.phone LIKE :term OR ct.mobile LIKE :term"), $query, $term, null, null, 'ct')->getQuery()->getResult(),
                ),
                'invoices' => array_map(
                    static fn (Invoice $i): SearchResult => new SearchResult('invoice', (string) $i->getId(), (string) $i->getInvoiceId(), (string) $i->getClient()?->getName(), '', $i->getStatus()?->value),
                    $this->filtered($this->invoiceRepository->createQueryBuilder('i')->innerJoin('i.client', 'c')->where('LOWER(i.invoiceId) LIKE :term OR LOWER(c.name) LIKE :term'), $query, $term, $status, $client, 'i')->getQuery()->getResult(),
                ),
                'quotes' => array_map(
                    static fn (Quote $q): SearchResult => new SearchResult('quote', (string) $q->getId(), (string) $q->getQuoteId(), (string) $q->getClient()?->getName(), '', $q->getStatus()?->value),
                    $this->filtered($this->quoteRepository->createQueryBuilder('q')->innerJoin('q.client', 'c')->where('LOWER(q.quoteId) LIKE :term OR LOWER(c.name) LIKE :term'), $query, $term, $status, $client, 'q')->getQuery()->getResult(),
                ),
                default => [],
            };

            if ($found !== []) {
                $results[$index] = $found;
            }
        }

        return $results;
    }

    private function filtered(QueryBuilder $qb, ParsedQuery $query, string $term, ?string $status, ?string $client, string $alias): QueryBuilder
    {
        $qb->setParameter('term', $term)->setMaxResults($query->hitsPerIndex);

        if ($status !== null) {
            $qb->andWhere($alias . '.status = :status')->setParameter('status', $status);
        }

        if ($client !== null) {
            $qb->andWhere('LOWER(c.name) LIKE :client')->setParameter('client', '%' . addcslashes(mb_strtolower($client), '%_\\') . '%');
        }

        return $qb;
    }

    /**
     * The value of a plain `attribute = "value"` filter the query parser built for this index.
     */
    private function qualifier(ParsedQuery $query, string $index, string $attribute): ?string
    {
        foreach ($query->indexFilters[$index] ?? [] as $expression) {
            if (preg_match('/^' . $attribute . ' = "([^"]*)"$/', $expression, $match) === 1) {
                return $match[1];
            }
        }

        return null;
    }
}
