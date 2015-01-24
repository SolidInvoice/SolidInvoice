<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use Carbon\Carbon;
use CSBill\CoreBundle\CSBillCoreBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Twig_Extension;

class GlobalExtension extends Twig_Extension
{

    /**
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * Sets the container
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get global twig variables
     *
     * @return array
     */
    public function getGlobals()
    {
        $appName = $this->container->get('settings')->get('system.general.app_name');

        return array(
            'query' => $this->getQuery(),
            'currency' => $this->container->get('currency'),
            'app_version' => CSBillCoreBundle::VERSION,
            'app_name' => $appName,
            'settings' => $this->container->get('settings')->getSettings()->toArray()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('percentage', array($this, 'formatPercentage')),
            new \Twig_SimpleFilter('currency', array($this, 'formatCurrency')),
            new \Twig_SimpleFilter('diff', array($this, 'dateDiff')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('icon', array($this, 'displayIcon'), array('is_safe' => array('html'))),
        );
    }

    /**
     * Displays an icon
     *
     * @param string $iconName
     * @param array  $options
     *
     * @return string
     */
    public function displayIcon($iconName, array $options = array())
    {
        $options = implode('-', $options);
        $class = sprintf('fa fa-%s', $iconName);

        if (!empty($options)) {
            $class .= ' '.$options;
        }

        return sprintf('<i class="%s"></i>', $class);
    }

    /**
     * @param int|float $amount
     *
     * @return string
     */
    public function formatCurrency($amount)
    {
        return $this->container->get('currency')->format($amount);
    }

    /**
     * @param int|float $amount
     * @param int       $percentage
     *
     * @return float|int
     */
    public function formatPercentage($amount, $percentage = 0)
    {
        if ($percentage > 0) {
            return ($amount * $percentage);
        }

        return 0;
    }

    /**
     * Returns a human-readible diff for dates
     *
     * @param \DateTime $date
     *
     * @return string
     */
    public function dateDiff(\DateTime $date)
    {
        $carbon = Carbon::instance($date);

        return $carbon->diffForHumans();
    }

    /**
     * Get the url query
     *
     * @throws InactiveScopeException
     * @return array
     */
    protected function getQuery()
    {
        try {
            $request = $this->container->get('request');

            $params = array_merge($request->query->all(), $request->attributes->all());

            foreach (array_keys($params) as $key) {
                if (substr($key, 0, 1) == '_') {
                    unset($params[$key]);
                }
            }

            return $params;
        } catch (InactiveScopeException $e) {
            return array();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'csbill_core.twig.globals';
    }
}
