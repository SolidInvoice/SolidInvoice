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
use Symfony\Component\Templating\EngineInterface;

class StatusColumn extends TextColumn
{
    /**
     * @var EngineInterface
     */
    private $twig;

    /**
     * @param EngineInterface $twig
     */
    public function __construct(EngineInterface $twig)
    {
        $this->twig = $twig;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function __initialize(array $params)
    {
        $this->callback = function ($value) {
            $function = $this->getParam('label_function');

            // @TODO: This should not call a string template
            return $this->twig->render(
                sprintf('{{ %s("%s") }}', $function, $value)
            );
        };

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