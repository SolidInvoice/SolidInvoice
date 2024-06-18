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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\DataGridBundle\Filter\FilterInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\Source\SourceInterface;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use Symfony\Component\HttpFoundation\Request;

//class Field{}

/*class SchemaBuilder
{
    private array $fields = [];

    public function addField(Field $field): self
    {
        $this->fields[] = $field;
    }
}*/

/*abstract class NewGrid implements GridInterface
{
    abstract public function entityFQCN(): string;
    abstract public function columns(): array;

    public function filters(): void
    {
    }
}

class ClientGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Client::class;
    }

    public function columns(): array
    {
        return [

        ];
    }
}*/

class Grid implements GridInterface
{
    public function entityFQCN(): string
    {
        return '';
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        return [];
    }

    /**
     * @return list<Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function batchActions(): array
    {
        return [];
    }

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
        private readonly ?SourceInterface $source = null,
        private readonly ?FilterInterface $filter = null,
        array $gridData = [],
        private readonly ?MoneyFormatterInterface $moneyFormatter = null
    ) {
        /*$this->title = $gridData['title'];
        $this->name = $gridData['name'];
        $this->columns = new ArrayCollection(array_values($gridData['columns']));
        $this->actions = $gridData['actions'];
        $this->lineActions = $gridData['line_actions'];
        $this->properties = $gridData['properties'];
        $this->icon = $gridData['icon'];*/
    }

    /**
     * @throws Exception
     */
    public function fetchData(Request $request, ObjectManager $objectManger): array
    {
        $queryBuilder = $this->source->fetch($this->parameters);

        $this->filter->filter($request, $queryBuilder);

        $paginator = new Paginator($queryBuilder);

        $resultSet = $paginator->getQuery()->getArrayResult();

        array_walk_recursive($resultSet, function (&$value, $key): void {
            if (is_string($key) && str_contains($key, 'currency')) {
                $value = $this->moneyFormatter->getCurrencySymbol($value);
            }
        });

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
        return [
            'title' => $this->title,
            'name' => $this->name,
            'columns' => $this->columns->toArray(),
            'actions' => $this->actions,
            'line_actions' => $this->lineActions,
            'properties' => $this->properties,
            'icon' => $this->icon,
            'parameters' => $this->parameters,
        ];
    }
}
