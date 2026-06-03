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

namespace SolidInvoice\CoreBundle\Telemetry\Message\Handler;

use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Telemetry\Message\SendTelemetryMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use function rtrim;
use function Sentry\captureException;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Telemetry\Message\Handler\SendTelemetryHandlerTest
 */
#[AsMessageHandler]
final readonly class SendTelemetryHandler
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'SOLIDINVOICE_TELEMETRY_URL')]
        private string $telemetryUrl,
    ) {
    }

    public function __invoke(SendTelemetryMessage $message): void
    {
        $endpoint = rtrim($this->telemetryUrl, '/') . '/v1/' . $message->type;

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $message->payload,
                'timeout' => 4,
                'max_duration' => 5,
            ]);

            // Reading the status code forces the request to complete. Any transport
            // error surfaces here and is caught below. A non-2xx status is treated
            // as a (silent) failure — telemetry is best-effort with no retries.
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->debug('Telemetry request returned a non-success status', [
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                ]);
            }
        } catch (Throwable $e) {
            // Swallow ALL failures and return normally so the message is acked and
            // never retried or moved to the failed queue. A slow or unreachable
            // Insights server must never degrade the app.
            $this->logger->debug('Telemetry request failed', [
                'endpoint' => $endpoint,
                'exception' => $e,
            ]);

            // Report to Sentry so we still learn about telemetry failures. This is a
            // no-op when Sentry is not enabled.
            captureException($e);
        }
    }
}
