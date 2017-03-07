<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Twig\Extension;

use CSBill\DashboardBundle\WidgetFactory;
use CSBill\DashboardBundle\Widgets\WidgetInterface;

class WidgetExtension extends \Twig_Extension
{
    /**
     * @var WidgetFactory
     */
    private $widgetFactory;

    /**
     * @param WidgetFactory $widgetFactory
     */
    public function __construct(WidgetFactory $widgetFactory)
    {
        $this->widgetFactory = $widgetFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('render_dashboard_widget', [$this, 'renderDashboardWidget'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Renders a dashboard widget at a specific location.
     *
     * @param \Twig_Environment $environment
     * @param string            $location
     *
     * @return string
     */
    public function renderDashboardWidget(\Twig_Environment $environment, $location)
    {
        /** @var WidgetInterface[] $widgets */
        $widgets = $this->widgetFactory->get($location);

        $content = '';

        foreach ($widgets as $widget) {
            $content .= $environment->render($widget->getTemplate(), $widget->getData());
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'dashboard_widget_extension';
    }
}
