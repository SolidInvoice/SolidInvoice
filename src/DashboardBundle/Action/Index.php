<?php

declare(strict_types=1);

namespace CSBill\DashboardBundle\Action;

use CSBill\CoreBundle\Templating\Template;
use Symfony\Component\HttpFoundation\Request;

class Index
{
    public function __invoke(Request $request)
    {
        return new Template('@CSBillDashboard/Default/index.html.twig');
    }
}
