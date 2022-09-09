<?php
declare(strict_types=1);

namespace SolidInvoice\MenuBundle\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

final class MenuBuilderFactory
{
    public function build(FactoryInterface $factory): ItemInterface
    {
        return $factory->createItem('root');
    }
}
