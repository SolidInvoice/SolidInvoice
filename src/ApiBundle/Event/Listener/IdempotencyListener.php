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

namespace SolidInvoice\ApiBundle\Event\Listener;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Uid\Uuid;
use function hash;
use function is_array;

/**
 * Replays the stored response for a repeated `Idempotency-Key` on operations that opt in
 * with `extraProperties: ['idempotent' => true]`. Keys are scoped to the user and company.
 */
final readonly class IdempotencyListener
{
    public const string HEADER = 'Idempotency-Key';

    private const string LOCK_ATTRIBUTE = '_idempotency_lock';

    private const string CACHE_KEY_ATTRIBUTE = '_idempotency_cache_key';

    private const string HASH_ATTRIBUTE = '_idempotency_hash';

    private const array REPLAYED_HEADERS = ['Content-Type', 'Content-Location', 'Location'];

    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        #[Autowire(service: 'cache.idempotency')]
        private CacheItemPoolInterface $cache,
        private LockFactory $lockFactory,
        private Security $security,
        private CompanySelectorInterface $companySelector,
    ) {
    }

    // After the firewall (8) has authenticated and selected the company, and after rate limiting (-10).
    #[AsEventListener(event: KernelEvents::REQUEST, priority: -20)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $key = $request->headers->get(self::HEADER);

        if ($key === null || ! $event->isMainRequest() || ! $this->isIdempotent($request)) {
            return;
        }

        if (! Uuid::isValid($key)) {
            $event->setResponse($this->problem(Response::HTTP_BAD_REQUEST, 'invalid_idempotency_key', 'The Idempotency-Key header must be a UUID.'));

            return;
        }

        $cacheKey = hash('sha256', $key . '|' . $this->security->getUser()?->getUserIdentifier() . '|' . $this->companySelector->getCompany()?->toRfc4122());
        // The headers that choose the response's representation are part of the request: replaying a body
        // in another money format or media type would hand the client something it did not ask for.
        $requestHash = hash('sha256', implode("\n", [
            $request->getMethod() . ' ' . $request->getPathInfo(),
            (string) $request->headers->get(ApiVersion::HEADER),
            (string) $request->headers->get('Accept'),
            $request->getContent(),
        ]));

        $lock = $this->lockFactory->createLock('idempotency_' . $cacheKey);

        if (! $lock->acquire()) {
            $event->setResponse($this->problem(Response::HTTP_CONFLICT, 'idempotency_key_in_use', 'A request with this Idempotency-Key is still being processed.'));

            return;
        }

        $stored = $this->cache->getItem($cacheKey)->get();

        if (! is_array($stored)) {
            $request->attributes->set(self::LOCK_ATTRIBUTE, $lock);
            $request->attributes->set(self::CACHE_KEY_ATTRIBUTE, $cacheKey);
            $request->attributes->set(self::HASH_ATTRIBUTE, $requestHash);

            return;
        }

        $lock->release();

        if ($stored['hash'] !== $requestHash) {
            $event->setResponse($this->problem(Response::HTTP_UNPROCESSABLE_ENTITY, 'idempotency_key_mismatch', 'This Idempotency-Key was already used for a different request.'));

            return;
        }

        $event->setResponse(new Response($stored['body'], $stored['status'], [...$stored['headers'], 'Idempotency-Replayed' => 'true']));
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $lock = $request->attributes->get(self::LOCK_ATTRIBUTE);

        if (! $lock instanceof LockInterface) {
            return;
        }

        $request->attributes->remove(self::LOCK_ATTRIBUTE);
        $response = $event->getResponse();

        try {
            // Only successes are kept: a failed request may be retried with the same key.
            if ($response->isSuccessful()) {
                $this->store($request, $response);
            }
        } finally {
            $lock->release();
        }
    }

    private function store(Request $request, Response $response): void
    {
        $headers = [];

        foreach (self::REPLAYED_HEADERS as $name) {
            if ($response->headers->has($name)) {
                $headers[$name] = $response->headers->get($name);
            }
        }

        $this->cache->save($this->cache->getItem((string) $request->attributes->get(self::CACHE_KEY_ATTRIBUTE))->set([
            'hash' => $request->attributes->get(self::HASH_ATTRIBUTE),
            'status' => $response->getStatusCode(),
            'headers' => $headers,
            'body' => $response->getContent(),
        ]));
    }

    private function isIdempotent(Request $request): bool
    {
        $resourceClass = $request->attributes->get('_api_resource_class');
        $operationName = $request->attributes->get('_api_operation_name');

        if ($request->getMethod() !== Request::METHOD_POST || $resourceClass === null || $operationName === null) {
            return false;
        }

        $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);

        return ($operation->getExtraProperties()['idempotent'] ?? false) === true;
    }

    private function problem(int $status, string $type, string $detail): JsonResponse
    {
        return new JsonResponse(
            ['type' => '/errors/' . $type, 'title' => Response::$statusTexts[$status], 'status' => $status, 'detail' => $detail],
            $status,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
