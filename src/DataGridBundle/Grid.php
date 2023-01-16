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

namespace SolidInvoice\DataGridBundle;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectManager;
use Exception;
use JsonSerializable;
use SolidInvoice\DataGridBundle\Filter\FilterInterface;
use SolidInvoice\DataGridBundle\Source\SourceInterface;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use Symfony\Component\HttpFoundation\Request;
use function array_map;

class Grid implements GridInterface, JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    private readonly ArrayCollection $columns;

    /**
     * @var array
     */
    private $actions;

    /**
     * @var array
     */
    private $lineActions;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $title;

    private array $parameters = [];

    public function __construct(
        private readonly SourceInterface $source,
        private readonly FilterInterface $filter,
        array $gridData,
        private readonly MoneyFormatterInterface $moneyFormatter
    ) {
        $this->title = $gridData['title'];
        $this->name = $gridData['name'];
        $this->columns = new ArrayCollection(array_values($gridData['columns']));
        $this->actions = $gridData['actions'];
        $this->lineActions = $gridData['line_actions'];
        $this->properties = $gridData['properties'];
        $this->icon = $gridData['icon'];
    }

    /**
     * @throws Exception
     */
    public function fetchData(Request $request, ObjectManager $objectManger): array
    {
        $queryBuilder = $this->source->fetch($this->parameters);

        $this->filter->filter($request, $queryBuilder);

        $paginator = new Paginator($queryBuilder);

        $resultSet = array_map(
            function (array $row) {
                foreach ($row as $key => $value) {
                    if (str_contains($key, '.')) {
                        [$column, $columnKey] = explode('.', $key);
                        $row[$column][$columnKey] = $value;

                        if ($columnKey === 'currency') {
                            $row[$column]['symbol'] = $this->moneyFormatter->getCurrencySymbol($value);
                        }

                        unset($row[$key]);
                    }
                }

                return $row;
            },
            $paginator->getQuery()->getArrayResult()
        );

        return [
            'count' => count($paginator),
            'items' => $resultSet,
        ];
    }

    public function requiresStatus(): bool
    {
        $criteria = Criteria::create();
        $criteria->where($criteria->expr()->contains('cell', 'status'));

        return count($this->columns->matching($criteria)) > 0;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setParameters(array $params): void
    {
        $this->parameters = $params;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'name' => $this->name,
            'columns' => $this->columns->toArray(),
            'actions' => $this->actions,
            'line-actions' => $this->lineActions,
            'properties' => $this->properties,
            'icon' => $this->icon,
            'parameters' => $this->parameters,
        ];
    }
}
