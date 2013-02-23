<?php

namespace CSBill\DataGridBundle\Grid;

use Symfony\Component\HttpFoundation\Request;
use Zend\Stdlib\CallbackHandler;

class Filter
{
	protected $active;

	protected $name;

	protected $callback;

	protected $options;

	public function __construct($name, $callback, $active = false, array $options = array())
	{
		$this->name = $name;
		$this->callback = is_callable($callback) ? new CallbackHandler($callback) : $callback;
		$this->active = $active;

		$this->options = $options;
	}

	public function isActive()
	{
		return $this->active;
	}

	public function getName()
	{
		return $this->name;
	}

	public function __invoke()
	{
		return call_user_func_array($this->callback, func_get_args());
	}

	public function __toString()
	{
		return $this->name;
	}

	public function getClass()
	{
		if($this->isActive())
		{
			return isset($this->options['active_class']) ? $this->options['active_class'] : '';
		} else {
			return isset($this->options['default_class']) ? $this->options['default_class'] : '';
		}
	}
}