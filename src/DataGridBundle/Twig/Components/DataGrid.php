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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionObject;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Exception\InvalidGridException;
use SolidInvoice\DataGridBundle\Filter\SearchFilter;
use SolidInvoice\DataGridBundle\Filter\SortFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Paginator\Adapter\QueryAdapter;
use SolidInvoice\DataGridBundle\Render\GridFieldRenderer;
use SolidInvoice\DataGridBundle\Source\SourceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use function array_map;
use function explode;
use function unserialize;

/**
 * @template T of object
 */
#[AsLiveComponent]
class DataGrid extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use ComponentWithFormTrait;

    /**
     * @var class-string<T>
     */
    #[LiveProp(writable: true, url: false)]
    public string $name;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true, hydrateWith: 'hydrateContext', dehydrateWith: 'dehydrateContext', url: false)]
    public array $context = [];

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true, url: true)]
    public string $sort = '';

    #[LiveProp(writable: true, url: true)]
    public int $perPage = 10;

    #[LiveProp(writable: true, url: true)]
    public string $search = '';

    /**
     * Hidden column field names.
     *
     * @var list<string>
     */
    #[LiveProp(writable: true, url: true)]
    public array $hiddenColumns = [];

    /**
     * @var list<string>
     */
    #[LiveProp(writable: true, onUpdated: 'onSelectedItem', url: false)]
    public array $selectedItems = [];

    #[LiveProp(writable: true, onUpdated: 'selectAll', url: false)]
    public bool $selectedAll = false;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true, url: true)]
    public array $gridFilters = [];

    /**
     * Initialize formValues from filters after mount.
     * This ensures URL-persisted filters are properly applied to the form.
     */
    #[PostMount(priority: 10)]
    public function initializeFormFromFilters(): void
    {
        if ($this->gridFilters !== []) {
            $this->formValues = $this->gridFilters;
        }
    }

    public function selectAll(): void
    {
        if ($this->selectedAll) {
            $this->selectedItems = [];
            foreach ($this->getPaginator() as $item) {
                $this->selectedItems[] = (string) $this->entityId($item);
            }
        } else {
            $this->selectedItems = [];
        }
    }

    public function onSelectedItem(): void
    {
        $totalItems = count($this->selectedItems);
        if (0 === $totalItems) {
            $this->selectedAll = false;
        } elseif ($totalItems === count($this->getPaginator())) {
            $this->selectedAll = true;
        }
    }

    /**
     * @param ServiceLocator<GridInterface> $serviceLocator
     */
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly GridFieldRenderer $fieldRenderer,
        private readonly SourceInterface $source,
        #[TaggedLocator(AsDataGrid::DI_TAG, 'name')]
        private readonly ServiceLocator $serviceLocator,
    ) {
    }

    /**
     * @return Pagerfanta<T>
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    #[ExposeInTemplate]
    public function getPaginator(): Pagerfanta
    {
        $grid = $this->getGrid();

        $query = $this->source->fetch($grid);
        $builder = $query->getQueryBuilder();

        $this->filterQuery($grid, $builder);

        try {
            return Pagerfanta::createForCurrentPageWithMaxPerPage(
                new QueryAdapter(
                    $builder,
                    beforeQuery: $query->getCallback(Query::BEFORE_QUERY),
                    afterQuery: $query->getCallback(Query::AFTER_QUERY),
                ),
                $this->page,
                $this->perPage,
            );
        } catch (OutOfRangeCurrentPageException) {
            $this->page = 1;

            return Pagerfanta::createForCurrentPageWithMaxPerPage(
                new QueryAdapter(
                    $builder,
                    beforeQuery: $query->getCallback(Query::BEFORE_QUERY),
                    afterQuery: $query->getCallback(Query::AFTER_QUERY),
                ),
                $this->page,
                $this->perPage,
            );
        }
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

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidGridException
     */
    #[ExposeInTemplate]
    public function getGrid(): GridInterface
    {
        try {
            $grid = $this->serviceLocator->get($this->name);
            $grid->initialize($this->context);

            return $grid;
        } catch (NotFoundExceptionInterface $e) {
            throw new InvalidGridException($this->name, $e);
        }
    }

    /**
     * @throws SyntaxError
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws LoaderError
     */
    public function renderField(Column $column, object $entity): string
    {
        return $this->fieldRenderer->render($column, $entity);
    }

    public function entityId(object $entity): mixed
    {
        $manager = $this->registry->getManagerForClass($entity::class);

        if ($manager === null) {
            throw new \RuntimeException(sprintf('No entity manager found for class "%s"', $entity::class));
        }

        $metaData = $manager->getClassMetadata($entity::class);

        return $metaData->getIdentifierValues($entity)[$metaData->getIdentifier()[0]];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidGridException
     */
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
            $this->selectedAll = false;
            $this->dispatchBrowserEvent('modal:close');
        }
    }

    /**
     * Execute a batch action on a single entity (from the row dropdown menu).
     *
     * @throws ContainerExceptionInterface
     * @throws InvalidGridException
     */
    #[LiveAction]
    public function executeSingleAction(
        #[LiveArg('actionName')]
        string $actionName,
        #[LiveArg('entityId')]
        string $entityId
    ): void {
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

            $actionFn($this->registry->getRepository($grid->entityFQCN()), [$entityId]);

            $this->addFlash('success', 'Success');
            $this->dispatchBrowserEvent('modal:close');

            return;
        }

        $this->addFlash('warning', 'Action not found.');
    }

    /**
     * Apply filters from the submitted form.
     * This is called when the user clicks "Apply Filters".
     */
    #[LiveAction]
    public function applyFilters(): void
    {
        // Get the submitted form values
        $this->submitForm();

        if ($this->formValues !== []) {
            $values = $this->formValues;
            unset($values['_token']);
            $this->gridFilters = $this->clearNestedValues($values);
        }

        // Reset page to 1 when filters change
        $this->page = 1;
    }

    #[LiveAction]
    public function clearFilters(): void
    {
        $this->gridFilters = [];
        $this->resetForm();
    }

    #[LiveAction]
    public function clearSelection(): void
    {
        $this->selectedItems = [];
        $this->selectedAll = false;
    }

    #[LiveAction]
    public function removeFilter(#[LiveArg('filterKey')] string $filterKey): void
    {
        unset($this->gridFilters[$filterKey]);
        $this->resetForm();
    }

    /**
     * Check if a column is visible.
     */
    public function isColumnVisible(string $field): bool
    {
        return ! in_array($field, $this->hiddenColumns, true);
    }

    /**
     * Get visible columns for the grid.
     *
     * @return list<Column>
     */
    #[ExposeInTemplate]
    public function getVisibleColumns(): array
    {
        return array_values(array_filter(
            $this->getGrid()->columns(),
            fn (Column $column) => $this->isColumnVisible($column->getField())
        ));
    }

    #[LiveAction]
    public function toggleColumn(#[LiveArg('field')] string $field): void
    {
        if (in_array($field, $this->hiddenColumns, true)) {
            $this->hiddenColumns = array_values(array_filter(
                $this->hiddenColumns,
                static fn (string $col) => $col !== $field
            ));
        } else {
            $this->hiddenColumns[] = $field;
        }
    }

    #[LiveAction]
    public function resetColumns(): void
    {
        $this->hiddenColumns = [];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidGridException
     */
    protected function instantiateForm(): FormInterface
    {
        $form = $this->createFormBuilder($this->gridFilters);

        foreach ($this->getGrid()->filters() as $name => $filter) {
            $form->add($name, $filter->form(), array_merge(['label' => false], $filter->formOptions()));
        }

        return $form->getForm();
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function clearNestedValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = $this->clearNestedValues($value);

                if ($value === []) {
                    unset($values[$key]);
                }
            } elseif ($value === '') {
                unset($values[$key]);
            }
        }

        return $values;
    }

    private function filterQuery(GridInterface $grid, QueryBuilder $builder): void
    {
        (new SortFilter(...explode(',', $this->sort)))->filter($builder, null);

        $searchFields = array_filter($grid->columns(), static fn (Column $column) => $column->isSearchable());
        $searchFields = array_map(static fn (Column $column) => $column->getField(), $searchFields);
        (new SearchFilter($searchFields))->filter($builder, $this->search);

        // Use the filters LiveProp (URL-persisted) instead of formValues
        foreach ($grid->filters() as $column => $filter) {
            $filterValue = $this->gridFilters[$column] ?? '';
            if ($filterValue !== '' && $filterValue !== []) {
                $filter->filter($builder, $filterValue);
            }
        }
    }

    public function title(): ?string
    {
        $grid = $this->getGrid();

        $gridDefinition = (new ReflectionObject($grid))->getAttributes(AsDataGrid::class)[0] ?? null;

        return $gridDefinition?->getArguments()['title'] ?? null;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function dehydrateContext(array $context): string
    {
        return serialize($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function hydrateContext(string $context): array
    {
        return unserialize($context, ['allowed_classes' => [Ulid::class]]);
    }
}
