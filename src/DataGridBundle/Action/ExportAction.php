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

namespace SolidInvoice\DataGridBundle\Action;

use JsonException;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\DataGridBundle\Exception\InvalidGridException;
use SolidInvoice\DataGridBundle\Export\ExportFilenameGenerator;
use SolidInvoice\DataGridBundle\Export\GridExporter;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use ValueError;
use function is_array;
use function is_string;
use function json_decode;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class ExportAction
{
    public function __construct(
        private readonly GridExporter $exporter,
        private readonly ExportFilenameGenerator $filenameGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $gridName = $request->query->get('grid');
        if (! is_string($gridName) || $gridName === '') {
            throw new BadRequestHttpException('Missing required "grid" query parameter.');
        }

        $format = $this->resolveFormat($request);
        $context = $this->resolveContext($request);

        $sort = (string) $request->query->get('sort', '');
        $search = (string) $request->query->get('search', '');
        $gridFilters = $request->query->all('gridFilters');

        try {
            $payload = $this->exporter->export($gridName, $format, $context, $sort, $search, $gridFilters);
        } catch (InvalidGridException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        $filename = $this->filenameGenerator->generate($gridName, $format, $gridFilters);

        $response = new Response($payload);
        $response->headers->set('Content-Type', $format->mimeType() . '; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
        );

        return $response;
    }

    private function resolveFormat(Request $request): ExportFormat
    {
        $format = (string) $request->query->get('format', ExportFormat::Csv->value);

        try {
            return ExportFormat::from($format);
        } catch (ValueError) {
            throw new BadRequestHttpException(sprintf('Unsupported export format "%s".', $format));
        }
    }

    /**
     * Decodes the JSON-encoded context query parameter (grid render context such as
     * `client_id` on a client detail page). Base58 ULID strings are consumed by each
     * grid's `query()` method via Doctrine's UlidType string coercion.
     *
     * @return array<string, mixed>
     */
    private function resolveContext(Request $request): array
    {
        $raw = (string) $request->query->get('context', '');

        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON in "context" query parameter.', $e);
        }

        if (! is_array($decoded)) {
            throw new BadRequestHttpException('"context" must decode to an object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
