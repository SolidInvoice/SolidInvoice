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

use CSBill\CoreBundle\CSBillCoreBundle;
use Twig_Extension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;

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
        $settings = $this->container->get('settings')->getSettings();

        return array(
                    'query'            => $this->getQuery(),
                    'currency'         => $this->container->get('currency'),
                    'settings'         => $settings,
                    'invoice_manager'  => $this->container->get('invoice.manager'),
                    'app_version'      => CSBillCoreBundle::VERSION,
                    'app_name'         => (string) $settings['system']['general']['app_name'],
            );
    }

    /**
     * (non-PHPdoc)
     * @see Twig_Extension::getFilters()
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('percentage', array($this, 'formatPercentage')),
            new \Twig_SimpleFilter('currency', array($this, 'formatCurrency')),
        );
    }

    /**
     * (non-PHPDoc)
     * @return \Twig_SimpleFunction[]
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
     * @param  string $iconName
     * @param  array  $options
     * @return string
     */
    public function displayIcon($iconName, array $options = array())
    {
        $options = implode('-', $options);
        $class = sprintf('fa fa-%s', $iconName);

        if (!empty($options)) {
            $class .= '-'.$options;
        }

        return sprintf('<i class="%s"></i>', $class);
    }

    /**
     * @param  int|float $amount
     * @return string
     */
    public function formatCurrency($amount)
    {
        return $this->container->get('currency')->format($amount);
    }

    /**
     * @param  int|float $amount
     * @param  int       $percentage
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
     * {inhertitDoc}
     */
    public function getName()
    {
        return 'csbill_core.twig.globals';
    }
}
