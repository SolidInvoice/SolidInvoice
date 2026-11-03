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

namespace SolidInvoice\ClientBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use function array_column;

#[Group('functional')]
final class ClientArchiveTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Client::class;
    }

    public function testArchiveHidesTheClientAndRestoreBringsItBack(): void
    {
        $client = ClientFactory::createOne(['status' => ClientStatus::Active]);
        $iri = $this->getIriFromResource($client);
        $kept = ClientFactory::createOne(['status' => ClientStatus::Active]);

        $data = $this->post($iri . '/archive');
        self::assertResponseStatusCodeSame(200);
        self::assertSame('archived', $data['status']);
        self::assertSame($iri, $data['@id']);

        self::assertSame([$this->getIriFromResource($kept)], $this->listIris('/api/clients?status=active'));
        self::assertSame([], $this->listIris('/api/clients?status=archived'));

        self::$client->request('GET', $iri, ['headers' => ['accept' => 'application/ld+json']]);
        self::assertResponseStatusCodeSame(404);

        $data = $this->post($iri . '/restore');
        self::assertResponseStatusCodeSame(200);
        self::assertSame('active', $data['status']);

        self::assertEqualsCanonicalizing([$iri, $this->getIriFromResource($kept)], $this->listIris('/api/clients?status=active'));
    }

    public function testArchiveAndRestoreAreIdempotent(): void
    {
        $client = ClientFactory::createOne(['status' => ClientStatus::Active]);
        $iri = $this->getIriFromResource($client);

        $this->post($iri . '/restore');
        self::assertResponseStatusCodeSame(200);

        $this->post($iri . '/archive');
        $data = $this->post($iri . '/archive');
        self::assertResponseStatusCodeSame(200);
        self::assertSame('archived', $data['status']);
    }

    public function testCannotArchiveOrRestoreAnotherCompanysClient(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $active = ClientFactory::createOne(['company' => $otherCompany, 'status' => ClientStatus::Active]);
        $archived = ClientFactory::createOne(['company' => $otherCompany, 'status' => ClientStatus::Archived, 'archived' => true]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $this->post($this->getIriFromResource($active) . '/archive');
        self::assertResponseStatusCodeSame(404);

        $this->post('/api/clients/' . $archived->getId() . '/restore');
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function post(string $uri): array
    {
        return self::$client->request('POST', $uri, ['headers' => ['accept' => 'application/ld+json']])->toArray(false);
    }

    /**
     * @return list<string>
     */
    private function listIris(string $uri): array
    {
        return array_column($this->requestGetCollection($uri)['member'], '@id');
    }
}
