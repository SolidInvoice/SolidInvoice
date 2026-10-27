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

namespace SolidInvoice\ApiBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function hash;

#[Group('functional')]
final class IdempotencyTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Client::class;
    }

    public function testSameKeyAndRequestReplaysTheStoredResponse(): void
    {
        $key = Uuid::v4()->toRfc4122();

        $first = $this->postClient($key, ['name' => 'Replayed']);
        self::assertSame(201, $first->getStatusCode());
        self::assertArrayNotHasKey('idempotency-replayed', $first->getHeaders(false));

        $second = $this->postClient($key, ['name' => 'Replayed']);
        self::assertSame(201, $second->getStatusCode());
        self::assertSame(['true'], $second->getHeaders(false)['idempotency-replayed']);
        self::assertSame($first->getContent(false), $second->getContent(false));
        self::assertSame($first->getHeaders(false)['location'] ?? null, $second->getHeaders(false)['location'] ?? null);

        self::assertSame(1, ClientFactory::repository()->count(['name' => 'Replayed']));
    }

    public function testSameKeyWithADifferentRequestIsRejected(): void
    {
        $key = Uuid::v4()->toRfc4122();

        self::assertSame(201, $this->postClient($key, ['name' => 'First'])->getStatusCode());

        $response = $this->postClient($key, ['name' => 'Second']);
        self::assertSame(422, $response->getStatusCode());
        self::assertStringEndsWith('idempotency_key_mismatch', $response->toArray(false)['type']);
        self::assertSame(0, ClientFactory::repository()->count(['name' => 'Second']));
    }

    public function testMalformedKeyIsRejected(): void
    {
        $response = $this->postClient('not-a-uuid', ['name' => 'Malformed']);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(0, ClientFactory::repository()->count(['name' => 'Malformed']));
    }

    public function testKeysAreScopedPerUser(): void
    {
        $key = Uuid::v4()->toRfc4122();
        $otherUser = UserFactory::createOne(['companies' => [$this->company]]);
        $otherToken = self::getContainer()->get(ApiTokenManager::class)->getOrCreate($otherUser, 'Functional Test')->plaintext;

        self::assertSame(201, $this->postClient($key, ['name' => 'Mine'])->getStatusCode());

        $response = $this->postClient($key, ['name' => 'Mine too'], ['X-API-TOKEN' => $otherToken]);
        self::assertSame(201, $response->getStatusCode());
        self::assertArrayNotHasKey('idempotency-replayed', $response->getHeaders(false));
        self::assertSame(1, ClientFactory::repository()->count(['name' => 'Mine too']));
    }

    public function testFailedResponsesAreNotStored(): void
    {
        $key = Uuid::v4()->toRfc4122();

        self::assertSame(422, $this->postClient($key, ['name' => ''])->getStatusCode());

        $response = $this->postClient($key, ['name' => 'Retried']);
        self::assertSame(201, $response->getStatusCode());
        self::assertArrayNotHasKey('idempotency-replayed', $response->getHeaders(false));
    }

    public function testKeyInUseByARunningRequestConflicts(): void
    {
        $key = Uuid::v4()->toRfc4122();
        $cacheKey = hash('sha256', $key . '|' . $this->user->getUserIdentifier() . '|' . $this->company->getId()->toRfc4122());
        $lock = self::getContainer()->get(LockFactory::class)->createLock('idempotency_' . $cacheKey);
        self::assertTrue($lock->acquire());

        try {
            self::assertSame(409, $this->postClient($key, ['name' => 'Running'])->getStatusCode());
        } finally {
            $lock->release();
        }

        self::assertSame(0, ClientFactory::repository()->count(['name' => 'Running']));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    private function postClient(string $key, array $data, array $headers = []): ResponseInterface
    {
        return self::$client->request('POST', '/api/clients', [
            'json' => $data,
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
                'Idempotency-Key' => $key,
                ...$headers,
            ],
        ]);
    }
}
