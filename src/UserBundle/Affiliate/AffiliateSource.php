<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Affiliate;

/**
 * Where a user reached the affiliate programme from. The backing value is sent as a telemetry
 * property and must remain stable. A page view with no source came from the profile navigation
 * or a typed URL.
 */
enum AffiliateSource: string
{
    case Banner = 'banner';
    case Page = 'page';
    case Footer = 'footer';
    case Dashboard = 'dashboard';
}
