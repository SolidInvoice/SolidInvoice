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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'twig' => [
        'debug' => param('kernel.debug'),
        'strict_variables' => param('kernel.debug'),
        'file_name_pattern' => '*.twig',
        'globals' => [
            'feedback_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSeJ5pJjGzLXaN_a2TjT4_rMYnl71hi7xYuLCopkhphg-VTfgg/viewform',
        ],
        'form_themes' => [
            '@SolidInvoiceNotification/Form/fields.html.twig',
            '@SolidInvoiceCore/Form/fields.html.twig',
            '@SolidInvoiceDataGrid/Form/fields.html.twig',
        ],
    ],
]);
