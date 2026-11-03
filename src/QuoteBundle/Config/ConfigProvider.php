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

namespace SolidInvoice\QuoteBundle\Config;

use SolidInvoice\CoreBundle\Form\Type\BillingIdConfigurationType;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ConfigProvider implements ProviderInterface
{
    /**
     * @return Config[]
     */
    public function provide(array $data): array
    {
        return [
            new Config('quote/watermark', '1', 'settings.description.quote.watermark', CheckboxType::class),
            new Config('quote/bcc_address', null, 'settings.description.quote.bcc_address', EmailType::class),
            new Config('quote/email_subject', 'New Quotation - #{id}', 'settings.description.quote.email_subject', TextType::class),
            new Config('quote/id_generation/strategy', 'auto_increment', '', BillingIdConfigurationType::class),
            new Config('quote/id_generation/id_prefix', '', 'settings.description.quote.id_generation.id_prefix', TextType::class),
            new Config('quote/id_generation/id_suffix', '', 'settings.description.quote.id_generation.id_suffix', TextType::class),
        ];
    }
}
