<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle;

use CSBill\DataGridBundle\Filter\FilterInterface;
use CSBill\DataGridBundle\Source\SourceInterface;
use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Serializer\ExclusionPolicy("ALL")
 */
class Grid implements GridInterface
{
    /**
     * @var string
     * @Serializer\Expose()
     */
    private $name;

    /**
     * @var ArrayCollection
     * @Serializer\Expose()
     */
    private $columns;

    /**
     * @var SourceInterface
     * @Serializer\Exclude()
     */
    private $source;

    /**
     * @var FilterInterface
     */
    private $filter;

    /**
     * @var array
     * @Serializer\Expose()
     */
    private $actions;

    /**
     * @var array
     * @Serializer\Expose()
     */
    private $lineActions;

    /**
     * @var array
     * @Serializer\Expose()
     */
    private $properties;

    /**
     * @var string
     * @Serializer\Expose()
     */
    private $icon;

    /**
     * @var string
     * @Serializer\Expose()
     */
    private $title;

    /**
     * @var array
     * @Serializer\Expose()
     */
    private $parameters = [];

    /**
     * @var MoneyFormatter
     */
    private $moneyFormatter;

    /**
     * @param SourceInterface $source
     * @param FilterInterface $filter
     * @param array           $gridData
     * @param MoneyFormatter  $moneyFormatter
     */
    public function __construct(SourceInterface $source, FilterInterface $filter, array $gridData, MoneyFormatter $moneyFormatter)
    {
        $this->title = $gridData['title'];
        $this->name = $gridData['name'];
        $this->columns = new ArrayCollection(array_values($gridData['columns']));
        $this->source = $source;
        $this->actions = $gridData['actions'];
        $this->lineActions = $gridData['line_actions'];
        $this->properties = $gridData['properties'];
        $this->icon = $gridData['icon'];
        $this->filter = $filter;
        $this->moneyFormatter = $moneyFormatter;
    }

    /**
     * @param Request       $request
     * @param ObjectManager $objectManger
     *
     * @return array
     *
     * @throws \Exception
     */
    public function fetchData(Request $request, ObjectManager $objectManger): array
    {
        $queryBuilder = $this->source->fetch($this->parameters);

        $this->filter->filter($request, $queryBuilder);

        $paginator = new Paginator($queryBuilder);

        $resultSet = $paginator->getQuery()->getArrayResult();

        array_walk_recursive($resultSet, function (&$value, $key): array {
            if (false !== strpos($key, 'currency')) {
                $value = $this->moneyFormatter->getCurrencySymbol($value);
            }
        });

        return [
            'count' => count($paginator),
            'items' => $resultSet,
        ];
    }

    /**
     * @return bool
     */
    public function requiresStatus(): bool
    {
        $criteria = Criteria::create();
        $criteria->where($criteria->expr()->contains('cell', 'status'));

        return count($this->columns->matching($criteria)) > 0;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param array $params
     */
    public function setParameters(array $params)
    {
        $this->parameters = $params;
    }
}
