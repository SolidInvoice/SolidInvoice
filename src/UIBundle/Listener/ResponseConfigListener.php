<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UIBundle\Listener;

use SolidInvoice\UIBundle\Config\UIConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function _\replace;
use function _\template;

class ResponseConfigListener implements EventSubscriberInterface
{
    private const META_TEMPLATE = '<meta name="app_config" content="<%- config %>">';

    /**
     * @var UIConfig
     */
    private $config;

    public function __construct(UIConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        $event->setResponse($this->addMetaConfig($response));
    }

    /**
     * @param Response $response
     *
     * @return Response
     * @throws \UnexpectedValueException
     */
    private function addMetaConfig(Response $response)
    {
        $content = $response->getContent();

        $template = template(self::META_TEMPLATE);

        $config = json_encode($this->config->all());

        $response->setContent(replace($content, '</head>', $template(['config' => $config]).'</head>'));

        return $response;
    }
}