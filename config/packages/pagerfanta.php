<?php
declare(strict_types=1);

use Symfony\Config\BabdevPagerfantaConfig;

return static function (BabdevPagerfantaConfig $config): void {
    $config->defaultView('twig');
    $config->defaultTwigTemplate('@SolidInvoiceDataGrid/pagination.html.twig');
};
