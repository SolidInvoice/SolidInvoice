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
use Ramsey\Uuid\Rfc4122\UuidV1;
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
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
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
    public array $filters = [];

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
        if ($this->formValues !== []) {
            $values = $this->formValues;
            unset($values['_token']);
            $this->filters = $this->clearNestedValues($values);
        }

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
        $metaData = $this->registry->getManagerForClass($entity::class)?->getClassMetadata($entity::class);

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

    #[LiveAction]
    public function clearFilters(): void
    {
        $this->filters = [];
        $this->resetForm();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidGridException
     */
    protected function instantiateForm(): FormInterface
    {
        $form = $this->createFormBuilder($this->filters);

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

        foreach ($grid->filters() as $column => $filter) {
            if ('' !== ($this->formValues[$column] ?? '')) {
                $filter->filter($builder, $this->formValues[$column]);
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
        return unserialize($context, ['allowed_classes' => [UuidV1::class]]);
    }
}
