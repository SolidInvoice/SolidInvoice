<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Twig\Extension;

use CSBill\DataGridBundle\Repository\GridRepository;
use JMS\Serializer\SerializerInterface;

class GridExtension extends \Twig_Extension
{
    private static $statusRendered = false;

    /**
     * @var GridRepository
     */
    private $repository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * GridExtension constructor.
     *
     * @param GridRepository      $repository
     * @param SerializerInterface $serializer
     */
    public function __construct(GridRepository $repository, SerializerInterface $serializer)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions()
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
     * @param string            $gridName
     * @param array             $parameters
     *
     * @return string
     *
     * @throws \CSBill\DataGridBundle\Exception\InvalidGridException
     */
    public function renderGrid(\Twig_Environment $env, $gridName, array $parameters = [])
    {
        $grid = $this->repository->find($gridName);

        if (!empty($parameters)) {
            $grid->setParameters($parameters);
        }

        $gridOptions = $this->serializer->serialize($grid, 'json');

        $html = '';

        if ($grid->requiresStatus() && false === self::$statusRendered) {
            $html .= $env->render('CSBillCoreBundle:_partials:status_labels.html.twig');
            self::$statusRendered = true;
        }

        $html .= $env->render(
            'CSBillDataGridBundle::grid.html.twig',
            [
                'gridName' => $gridName,
                'gridOptions' => $gridOptions,
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
     * @throws \CSBill\DataGridBundle\Exception\InvalidGridException
     */
    public function renderMultipleGrid(\Twig_Environment $env)
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

            $gridOptions = $this->serializer->serialize($grid, 'json');

            $requiresStatus = $requiresStatus || $grid->requiresStatus();

            $renderGrids[$gridName] = json_decode($gridOptions, true);
        }

        return $env->render(
            'CSBillDataGridBundle::multiple_grid.html.twig',
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
