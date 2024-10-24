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

namespace SolidInvoice\DataGridBundle\Twig\Extension;

use JsonException;
use SolidInvoice\DataGridBundle\Exception\InvalidGridException;
use SolidInvoice\DataGridBundle\Repository\GridRepository;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GridExtension extends AbstractExtension
{
    public function __construct(
        private readonly GridRepository $repository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'render_grid',
                fn (Environment $env, string $gridName, array $parameters = []): string => $this->renderGrid($env, $gridName, $parameters),
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'render_multiple_grid',
                fn (Environment $env, ...$args): string => $this->renderMultipleGrid($env, ...$args),
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * @param array<string, string> $parameters
     * @throws InvalidGridException|JsonException|LoaderError|RuntimeError|SyntaxError
     */
    public function renderGrid(Environment $env, string $gridName, array $parameters = []): string
    {
        $grid = $this->repository->find($gridName);

        if ($parameters !== []) {
            $grid->setParameters($parameters);
        }

        $gridOptions = json_encode($grid, JSON_THROW_ON_ERROR);

        return $env->render(
            '@SolidInvoiceDataGrid/grid.html.twig',
            [
                'gridName' => $gridName,
                'gridOptions' => $gridOptions,
            ]
        );
    }

    /**
     * @throws InvalidGridException|JsonException|LoaderError|RuntimeError|SyntaxError
     */
    public function renderMultipleGrid(Environment $env): string
    {
        $parameters = [];

        $args = func_get_args();

        $grids = array_splice($args, 1);

        if (is_array($grids[0])) {
            if (! empty($grids[1]) && is_array($grids[1])) {
                $parameters = $grids[1];
            }

            $grids = $grids[0];
        }

        $renderGrids = [];

        foreach ($grids as $gridName) {
            $grid = $this->repository->find($gridName);
            $grid->setParameters($parameters);

            $gridOptions = json_encode($grid, JSON_THROW_ON_ERROR);

            $renderGrids[$gridName] = json_decode($gridOptions, true, 512, JSON_THROW_ON_ERROR);
        }

        return $env->render(
            '@SolidInvoiceDataGrid/multiple_grid.html.twig',
            [
                'grids' => $renderGrids,
            ]
        );
    }
}
