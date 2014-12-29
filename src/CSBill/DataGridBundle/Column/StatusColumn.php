<?php

namespace CSBill\DataGridBundle\Column;

use APY\DataGridBundle\Grid\Column\TextColumn;
use Symfony\Component\Templating\EngineInterface;

class StatusColumn extends TextColumn
{
    /**
     * @param EngineInterface $twig
     */
    public function __construct(EngineInterface $twig)
    {
        $this->callback = function ($value, \APY\DataGridBundle\Grid\Row $row) use ($twig) {
            $label = $row->getField('status.label');

            $twig->render('CSBillCoreBundle:Status:label.html.twig',
                array(
                    'entity' => array(
                        'label' => $label,
                        'name' => $value
                    ),
                )
            );

            return '<span class="label label-' . $label . '">' . ucfirst($value) . '</span>';
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