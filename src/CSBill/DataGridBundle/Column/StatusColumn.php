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
use Symfony\Component\Templating\EngineInterface;

class StatusColumn extends TextColumn
{
    /**
     * @param EngineInterface $twig
     */
    public function __construct(EngineInterface $twig)
    {
        $this->callback = function ($value, Row $row) use ($twig) {
            $label = $row->getField('status.label');

            return $twig->render('CSBillCoreBundle:Status:label.html.twig',
                array(
                    'entity' => array(
                        'label' => $label,
                        'name' => $value
                    ),
                )
            );
        };

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function __initialize(array $params)
    {
        $params['safe'] = false;

        parent::__initialize($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'status';
    }
}