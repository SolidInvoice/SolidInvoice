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

namespace SolidInvoice\ClientBundle\Action\Ajax;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Info implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly Environment $twig,
        private readonly MoneyFormatterInterface $formatter,
    ) {
    }

    /**
     * @throws SyntaxError|RuntimeError|LoaderError
     */
    public function __invoke(Client $client, string $type = 'quote'): JsonResponse
    {
        $content = $this->twig->render(
            '@SolidInvoiceClient/Ajax/info.html.twig',
            [
                'client' => $client,
                'type' => $type,
            ]
        );

        return $this->json([
            'content' => $content,
            'currency' => $client->getCurrency(),
            'currency_format' => $this->formatter->getCurrencySymbol($client->getCurrency()),
        ]);
    }
}
