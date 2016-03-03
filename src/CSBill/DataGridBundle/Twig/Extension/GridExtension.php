<?php

namespace CSBill\DataGridBundle\Twig\Extension;

use CSBill\DataGridBundle\Repository\GridRepository;
use JMS\Serializer\SerializerInterface;

class GridExtension extends \Twig_Extension
{
    /**
     * @var GridRepository
     */
    private $repository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private static $statusRendered = false;

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
		    'needs_environment' => true
		]
	    )
	];
    }

    /**
     * @param \Twig_Environment $env
     * @param string            $gridName
     *
     * @return string
     * @throws \CSBill\DataGridBundle\Exception\InvalidGridException
     */
    public function renderGrid(\Twig_Environment $env, $gridName)
    {

	$grid = $this->repository->find($gridName);
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
		'requiresStatus' => $grid->requiresStatus()
	    ]
	);

	return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
	return 'grid_extension';
    }
}