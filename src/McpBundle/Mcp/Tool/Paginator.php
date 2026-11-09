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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Doctrine\ORM\QueryBuilder;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use function max;
use function min;
use function sprintf;

/**
 * Runs a list tool's query one page at a time and wraps the normalised rows in the shared
 * {@see ListOutput} envelope. Company isolation comes from the CompanyFilter, as for every query.
 */
final readonly class Paginator
{
    public const int MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private EntityNormalizer $normalizer,
    ) {
    }

    /**
     * @param int $page         1-indexed; anything lower reads as 1
     * @param int $itemsPerPage clamped to 1..{@see self::MAX_ITEMS_PER_PAGE}
     */
    public function paginate(QueryBuilder $qb, int $page, int $itemsPerPage): ListOutput
    {
        $itemsPerPage = max(1, min($itemsPerPage, self::MAX_ITEMS_PER_PAGE));
        $page = max(1, $page);

        $total = (int) (clone $qb)
            ->select(sprintf('COUNT(%s)', $qb->getRootAliases()[0]))
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $results = $qb
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        return new ListOutput($this->normalizer->normalizeMany($results), $page, $itemsPerPage, $total);
    }
}
