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
use function array_filter;
use function explode;
use function is_array;
use function is_scalar;
use function is_string;
use function json_decode;
use function preg_match;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final readonly class ExportAction
{
    public function __construct(
        private GridExporter $exporter,
        private ExportFilenameGenerator $filenameGenerator,
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

        $sort = $this->resolveSort($request);
        $search = (string) $request->query->get('search', '');
        $gridFilters = $this->resolveGridFilters($request);

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
     * Validates the `sort` query parameter to one of: empty string, `field`, or
     * `field,direction`. Anything else is rejected. This keeps malformed input from
     * unpacking into SortFilter's two-argument constructor and triggering
     * ArgumentCountError downstream.
     */
    private function resolveSort(Request $request): string
    {
        $sort = (string) $request->query->get('sort', '');

        if ($sort === '') {
            return '';
        }

        $parts = explode(',', $sort);
        if (count($parts) > 2) {
            throw new BadRequestHttpException('Malformed "sort" query parameter.');
        }

        if (! preg_match('/^[A-Za-z0-9_.]+$/', $parts[0])) {
            throw new BadRequestHttpException('Invalid sort field.');
        }

        $direction = strtolower($parts[1] ?? 'asc');
        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new BadRequestHttpException('Sort direction must be "asc" or "desc".');
        }

        return $parts[0] . ',' . $direction;
    }

    /**
     * Validates `gridFilters` shape. Each value must be a scalar, an array of scalars,
     * or an associative array with `start`/`end` keys (the date-range filter). Anything
     * else is rejected before reaching column filter implementations, which assume
     * specific shapes via assertions disabled in production.
     *
     * @return array<string, mixed>
     */
    private function resolveGridFilters(Request $request): array
    {
        $raw = $request->query->all('gridFilters');

        foreach ($raw as $key => $value) {
            if (! is_string($key) || ! preg_match('/^\w+$/', $key)) {
                throw new BadRequestHttpException('Invalid grid filter key.');
            }

            if (is_scalar($value) || $value === null) {
                continue;
            }

            if (is_array($value)) {
                $allScalar = array_filter($value, static fn ($v): bool => ! is_scalar($v) && $v !== null) === [];
                if (! $allScalar) {
                    throw new BadRequestHttpException(sprintf('Filter "%s" has unsupported nested values.', $key));
                }

                continue;
            }

            throw new BadRequestHttpException(sprintf('Filter "%s" has unsupported value type.', $key));
        }

        return $raw;
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

        // Defense-in-depth: context values are forwarded to each grid's initialize()
        // method, which typically binds them as Doctrine parameters. Match the same
        // key whitelist used for `gridFilters` so a tampered context cannot smuggle
        // unexpected identifier shapes through.
        foreach ($decoded as $key => $value) {
            if (! is_string($key) || ! preg_match('/^\w+$/', $key)) {
                throw new BadRequestHttpException('Invalid context key.');
            }

            if ($value !== null && ! is_scalar($value)) {
                throw new BadRequestHttpException(sprintf('Context value for "%s" must be a scalar or null.', $key));
            }
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
