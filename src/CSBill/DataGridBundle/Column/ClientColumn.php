<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Column;

use APY\DataGridBundle\Grid\Column\TextColumn;
use APY\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\RouterInterface;

class ClientColumn extends TextColumn
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function __initialize(array $params)
    {
        /** @var \Twig_Template $template */
        static $template;

        $params['safe'] = false;

        parent::__initialize($params);

        $function = $this->getParam('label_function');

        if (!isset($template)) {
            $template = $this->twig->createTemplate(
                sprintf('<a href="{{ route }}">{{ client_name }}</a>', $function)
            );
        }

        $this->callback = function ($clientName, Row $row, RouterInterface $router) use ($template) {
            $clientId = $row->getField('client.id');

            if (!empty($clientId)) {
                $route = $router->generate('_clients_view', array('id' => $clientId));

                return $template->render(array('route' => $route, 'client_name' => $clientName));
            }

            return $clientName;
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'client';
    }
}