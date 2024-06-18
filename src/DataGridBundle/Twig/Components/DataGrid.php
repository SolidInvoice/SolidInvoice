<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Twig\Components;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Filter\ChainFilter;
use SolidInvoice\DataGridBundle\Filter\SearchFilter;
use SolidInvoice\DataGridBundle\Filter\SortFilter;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\ColumnFormatter;
use SolidInvoice\DataGridBundle\Source\SourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function array_map;
use function explode;

#[AsLiveComponent]
class DataGrid extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true, url: true)]
    public string $name = '';

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true, url: true)]
    public string $sort = '';

    #[LiveProp(writable: true, url: true)]
    public int $perPage = 10;

    #[LiveProp(writable: true, url: true)]
    public string $search = '';

    #[LiveProp(writable: true, url: false)]
    public array $selectedItems = [];

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ColumnFormatter $columnFormatter,
        private readonly SourceInterface $source,
        #[TaggedLocator(AsDataGrid::DI_TAG, 'name')]
        private readonly ServiceLocator $serviceLocator,
    ) {
    }

    /**
     * @throws Exception
     */
    #[ExposeInTemplate]
    public function getPaginator(): Pagerfanta
    {
        $grid = $this->getGrid();

        $qb = $this->source->fetch($grid);

        $filter = new ChainFilter();
        $filter->addFilter(new SortFilter(...explode(',', $this->sort)));
        $filter->addFilter(new SearchFilter($this->search, array_map(static fn (Column $column) => $column->getField(), $grid->columns())));

        $filter->filter($qb);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($qb),
            $this->page,
            $this->perPage,
        );
    }

    #[ExposeInTemplate]
    public function sortDir(): string
    {
        return explode(',', $this->sort)[1] ?? Criteria::ASC;
    }

    #[ExposeInTemplate]
    public function sortField(): string
    {
        return explode(',', $this->sort)[0] ?? '';
    }

    public function renderField(Column $column, object $entity): string
    {
        $value = $this->propertyAccessor->getValue($entity, $column->getField());

        return $this->columnFormatter->format($column, $value);
    }

    public function entityId(object $entity): mixed
    {
        $metaData = $this->registry->getManagerForClass($entity::class)?->getClassMetadata($entity::class);

        return $metaData->getIdentifierValues($entity)[$metaData->getIdentifier()[0]];
    }

    #[LiveAction]
    public function executeBatchAction(#[LiveArg('actionName')] string $actionName): void
    {
        try {
            if ($this->selectedItems === []) {
                $this->addFlash('warning', 'Please select at least one item.');
                return;
            }

            $grid = $this->getGrid();

            foreach ($grid->batchActions() as $action) {
                if ($action->getLabel() !== $actionName) {
                    continue;
                }

                $actionFn = $action->getAction();

                if (null === $actionFn) {
                    $this->addFlash('warning', 'Action not implemented.');
                    return;
                }

                $actionFn($this->registry->getRepository($grid->entityFQCN()), $this->selectedItems);

                $this->addFlash('success', 'Success');

                return;
            }
        } finally {
            $this->selectedItems = [];
            $this->dispatchBrowserEvent('modal:close');
        }
    }

    #[ExposeInTemplate]
    public function getGrid(): Grid
    {
        return $this->serviceLocator->get($this->name);
    }
}
