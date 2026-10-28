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
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\DashboardBundle\Activity\ActivityFeed;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;
use function array_map;
use function array_slice;
use function count;
use function sprintf;

/**
 * Recent activity, newest first, 30 a page: company-wide, or for one client.
 */
#[AsController]
final readonly class Activity
{
    private const int PER_PAGE = 30;

    // ponytail: each page re-reads every source up to its offset; an activity table would page cheaply.
    private const int MAX_PAGE = 20;

    public function __construct(
        private ActivityFeed $feed,
        private ClientRepository $clientRepository,
        private CurrencyScale $currencyScale,
        private IriConverterInterface $iriConverter,
    ) {
    }

    #[Route(path: '/activity', name: 'api_activity', methods: ['GET'])]
    public function company(Request $request): JsonResponse
    {
        return $this->page($request, null);
    }

    #[Route(path: '/clients/{id}/activity', name: 'api_client_activity', requirements: ['id' => Requirement::ULID], methods: ['GET'])]
    public function client(Request $request, string $id): JsonResponse
    {
        // find() applies the CompanyFilter, so another company's client is not found.
        $client = $this->clientRepository->find(Ulid::fromString($id));

        if (! $client instanceof Client) {
            throw new NotFoundHttpException('Client not found.');
        }

        return $this->page($request, $client);
    }

    private function page(Request $request, ?Client $client): JsonResponse
    {
        $page = $request->query->getInt('page', 1);

        if ($page < 1 || $page > self::MAX_PAGE) {
            throw new BadRequestHttpException(sprintf('page must be between 1 and %d.', self::MAX_PAGE));
        }

        // One more than a page, to know whether there is a next one.
        $entries = $this->feed->entries(self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE, $client);
        $hasNext = count($entries) > self::PER_PAGE && $page < self::MAX_PAGE;

        $response = new JsonResponse([
            'member' => array_map($this->item(...), array_slice($entries, 0, self::PER_PAGE)),
            'page' => $page,
            'itemsPerPage' => self::PER_PAGE,
            'next' => $hasNext ? $request->getPathInfo() . '?page=' . ($page + 1) : null,
        ]);
        $response->setPrivate();

        return $response;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array<string, mixed>
     *
     * @throws MathException
     */
    private function item(array $entry): array
    {
        $type = $entry['type'] === ActivityFeed::PAYMENT ? 'payment_received' : $entry['type'];
        $date = $entry['date'];

        $item = [
            'type' => $type,
            'occurredAt' => $date instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($date)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z') : null,
            'subject' => $this->subject($entry),
            'client' => $entry['clientId'] === null ? null : [
                'iri' => $this->iri(Client::class, (string) $entry['clientId']),
                'name' => $entry['client'],
            ],
        ];

        if (isset($entry['amount'], $entry['currency'])) {
            $currency = $entry['currency'] instanceof Currency ? $entry['currency'] : new Currency((string) $entry['currency']);
            $item['amount'] = $this->currencyScale->toDecimalString(BigNumber::of($entry['amount']), $currency);
            $item['currency'] = $currency->getCode();
        }

        if (isset($entry['method'])) {
            $item['method'] = $entry['method'];
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array{iri: string, label: string}
     */
    private function subject(array $entry): array
    {
        return match (true) {
            $entry['type'] === ActivityFeed::PAYMENT => ['iri' => $this->iri(Invoice::class, (string) $entry['invoiceUlid']), 'label' => (string) $entry['invoiceId']],
            isset($entry['invoiceId']) => ['iri' => $this->iri(Invoice::class, (string) $entry['id']), 'label' => (string) $entry['invoiceId']],
            isset($entry['quoteId']) => ['iri' => $this->iri(Quote::class, (string) $entry['id']), 'label' => (string) $entry['quoteId']],
            default => ['iri' => $this->iri(Client::class, (string) $entry['id']), 'label' => (string) $entry['client']],
        };
    }

    /**
     * @param class-string $class
     */
    private function iri(string $class, string $id): string
    {
        return $this->iriConverter->getIriFromResource($class, UrlGeneratorInterface::ABS_PATH, context: ['uri_variables' => ['id' => $id]]);
    }
}
