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

namespace SolidInvoice\CoreBundle\Mode;

/**
 * Actions whose availability depends on the application run mode (see ModeResolver).
 * Distinct from solidworx/toggler (capability wired?) and the SaaS plan FeatureGate (plan includes?).
 */
enum Capability
{
    case UserRegistration;
    case RealEmailDelivery;
    case RealNotificationDelivery;
    case OnlinePaymentCapture;
    case CredentialChange;
}
