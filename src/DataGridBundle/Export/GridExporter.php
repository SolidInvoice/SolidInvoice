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

namespace SolidInvoice\DataGridBundle\Export;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Export\Serializer\ExportSerializer;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Exception\InvalidGridException;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Source\SourceInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Runs a grid's filter pipeline end-to-end and encodes the full (unpaginated) result
 * set into the chosen export format.
 *
 * Only sync/in-memory exports are supported today. The full result set is materialized
 * before encoding.
 *
 * TODO(async-export): when datasets grow large enough to cause memory pressure, move
 *   this behind a Messenger job that writes chunks to disk and emails the user a
 *   download link, mirroring the full-company export flow.
 */
final readonly class GridExporter
{
    /**
     * @param ServiceLocator<GridInterface> $gridLocator
     */
    public function __construct(
        #[AutowireLocator(AsDataGrid::DI_TAG, 'name')]
        private ServiceLocator $gridLocator,
        private SourceInterface $source,
        private GridQueryService $gridQueryService,
        private GridRowExtractor $rowExtractor,
        private ExportSerializer $serializer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $gridFilters
     *
     * @throws InvalidGridException
     * @throws ContainerExceptionInterface
     */
    public function export(
        string $gridName,
        ExportFormat $format,
        array $context,
        string $sort,
        string $search,
        array $gridFilters,
    ): string {
        $grid = $this->resolveGrid($gridName);
        $grid->initialize($context);

        $query = $this->source->fetch($grid);
        $builder = $query->getQueryBuilder();

        $this->gridQueryService->applyFilters($grid, $builder, $sort, $search, $gridFilters);

        $beforeQuery = $query->getCallback(Query::BEFORE_QUERY);
        if ($beforeQuery instanceof Closure) {
            $beforeQuery($builder);
        }

        /** @var list<object> $results */
        $results = $builder->getQuery()->getResult();

        $afterQuery = $query->getCallback(Query::AFTER_QUERY);
        if ($afterQuery instanceof Closure) {
            $afterQuery($results);
        }

        $columns = $grid->columns();
        // TODO(export-columns): allow users to select which columns are included.
        // For now every declared column is exported regardless of hiddenColumns.

        $rows = [];
        foreach ($results as $entity) {
            $rows[] = $this->rowExtractor->extract($columns, $entity);
        }

        // XML element names cannot contain most punctuation. Grid names are snake_case
        // identifiers so this is mostly defensive.
        $xmlRoot = (string) preg_replace('/[^A-Za-z0-9_]/', '_', $gridName);

        return $this->serializer->encode($rows, $format, $format->encoderContext($xmlRoot));
    }

    /**
     * @throws InvalidGridException
     * @throws ContainerExceptionInterface
     */
    private function resolveGrid(string $gridName): GridInterface
    {
        try {
            return $this->gridLocator->get($gridName);
        } catch (NotFoundExceptionInterface $e) {
            throw new InvalidGridException($gridName, $e);
        }
    }
}
