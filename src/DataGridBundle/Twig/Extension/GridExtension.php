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

class GridExtension extends \Twig_Extension
{
    private static $statusRendered = false;

    /**
     * @var GridRepository
     */
    private $repository;

    /**
     * @param GridRepository $repository
     */
    public function __construct(GridRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction(
                'render_grid',
                [$this, 'renderGrid'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
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
     * @param \Twig_Environment $env
     * @param string            $name
     * @param array             $parameters
     *
     * @return string
     *
     * @throws \SolidInvoice\DataGridBundle\Exception\InvalidGridException|\Twig_Error
     */
    public function renderGrid(\Twig_Environment $env, string $name, array $parameters = []): string
    {
        $grid = $this->repository->find($name);

        if (!empty($parameters)) {
            $grid->setParameters($parameters);
        }

        $html = '';

        if ($grid->requiresStatus() && false === self::$statusRendered) {
            $html .= $env->render('@SolidInvoiceCore/_partials/status_labels.html.twig');
            self::$statusRendered = true;
        }

        $html .= $env->render(
            '@SolidInvoiceDataGrid/grid.html.twig',
            [
                'name' => $name,
                'grid' => $grid,
                'requiresStatus' => $grid->requiresStatus(),
            ]
        );

        return $html;
    }

    /**
     * @param \Twig_Environment $env
     *
     * @return string
     *
     * @throws \SolidInvoice\DataGridBundle\Exception\InvalidGridException
     */
    public function renderMultipleGrid(\Twig_Environment $env): string
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

        $requiresStatus = false;

        $renderGrids = [];

        foreach ($grids as $gridName) {
            $grid = $this->repository->find($gridName);
            $grid->setParameters($parameters);

            $gridOptions = json_encode($grid);

            $requiresStatus = $requiresStatus || $grid->requiresStatus();

            $renderGrids[$gridName] = json_decode($gridOptions, true);
        }

        return $env->render(
            'SolidInvoiceDataGridBundle::multiple_grid.html.twig',
            [
                'grids' => $renderGrids,
                'requiresStatus' => $requiresStatus,
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
