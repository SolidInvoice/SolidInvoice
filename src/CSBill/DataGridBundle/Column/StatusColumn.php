<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Column;

use APY\DataGridBundle\Grid\Column\TextColumn;

class StatusColumn extends TextColumn
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
        /* @var \Twig_Template[] $template */
        static $template = array();

        $params['safe'] = false;

        parent::__initialize($params);

        $function = $this->getParam('label_function');

        if (!isset($template[$function])) {
            $template[$function] = $this->twig->createTemplate(
                sprintf('{{ %s(status) }}', $function)
            );
        }

        $this->callback = function ($value) use ($template, $function) {
            return $template[$function]->render(array('status' => $value));
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'status';
    }
}
