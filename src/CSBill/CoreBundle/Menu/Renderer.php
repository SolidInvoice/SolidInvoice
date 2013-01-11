<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\Renderer\ListRenderer;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Silex\Voter\RouteVoter;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;

class Renderer extends ListRenderer
{
	/**
	 * @var FactoryInterface
	 */
	protected $factory;

	/**
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Constructor
	 *
	 * @param ContainerInterface $container
	 * @param FactoryInterface $factory
	 */
	public function __construct(ContainerInterface $container, FactoryInterface $factory)
	{
		$this->container = $container;
		$this->factory = $factory;

		$matcher = new Matcher();

		try {
			$request = $this->container->get('request');

			$voter = new RouteVoter($request->get('_route'));

			$voter->setRequest($request);
			$matcher->addVoter($voter);
		} catch (InactiveScopeException $e)
		{
			// We are most probably running from the command line, which means there is no 'request' service. We just gracefully continue
		}

		parent::__construct($matcher, array('currentClass' => 'active'));
	}

	/**
	 * Renders a menu at a specific location
	 *
	 * @param string $location The location to render the menu
	 */
	public function build($storage, array $options = array())
	{
		$menu = $this->factory->createItem('root');

		$menu->setChildrenAttributes(array('class' => 'nav nav-list'));

		foreach($storage as $builder)
		{
			$builder->invoke($menu, $options);
		}

		return $this->render($menu, $options);
	}
}
