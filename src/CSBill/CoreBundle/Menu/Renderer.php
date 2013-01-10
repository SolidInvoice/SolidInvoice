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

		$voter = new RouteVoter($route);

		$voter->setRequest($this->container->get('request'));

		$matcher = new Matcher();
		$matcher->addVoter($voter);

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

		foreach($storage as $builder)
		{
			$builder->invoke($menu, $options);
		}

		return $this->render($menu, $options);
	}
}
