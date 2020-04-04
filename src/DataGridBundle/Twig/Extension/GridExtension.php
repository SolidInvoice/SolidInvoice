<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Twig\Extension;

use SolidInvoice\DataGridBundle\Repository\GridRepository;

class GridExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @var GridRepository
     */
    private $repository;

    public function __construct(GridRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'render_grid',
                [$this, 'renderGrid'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig\TwigFunction(
                'render_multiple_grid',
                [$this, 'renderMultipleGrid'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * @throws \SolidInvoice\DataGridBundle\Exception\InvalidGridException
     */
    public function renderGrid(\Twig\Environment $env, string $gridName, array $parameters = []): string
    {
        $grid = $this->repository->find($gridName);

        if (!empty($parameters)) {
            $grid->setParameters($parameters);
        }

        $gridOptions = json_encode($grid);

        return $env->render(
            '@SolidInvoiceDataGrid/grid.html.twig',
            [
                'gridName' => $gridName,
                'gridOptions' => $gridOptions,
            ]
        );
    }

    /**
     * @throws \SolidInvoice\DataGridBundle\Exception\InvalidGridException
     */
    public function renderMultipleGrid(\Twig\Environment $env): string
    {
        $parameters = [];

        $args = func_get_args();
        $grids = array_splice($args, 1);

        if (is_array($grids[0])) {
            if (!empty($grids[1]) && is_array($grids[1])) {
                $parameters = $grids[1];
            }

            $grids = $grids[0];
        }

        $renderGrids = [];

        foreach ($grids as $gridName) {
            $grid = $this->repository->find($gridName);
            $grid->setParameters($parameters);

            $gridOptions = json_encode($grid);

            $renderGrids[$gridName] = json_decode($gridOptions, true);
        }

        return $env->render(
            '@SolidInvoiceDataGrid/multiple_grid.html.twig',
            [
                'grids' => $renderGrids,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'grid_extension';
    }
}
