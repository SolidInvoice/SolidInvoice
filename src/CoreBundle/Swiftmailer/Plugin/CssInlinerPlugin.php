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

namespace SolidInvoice\CoreBundle\Swiftmailer\Plugin;

use Symfony\Component\DomCrawler\Crawler;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CssInlinerPlugin implements \Swift_Events_SendListener
{
    /**
     * @var CssToInlineStyles
     */
    private $inliner;

    /**
     * @param CssToInlineStyles $inliner
     */
    public function __construct(CssToInlineStyles $inliner)
    {
        $this->inliner = $inliner;
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();

        $this->convert($message);

        $children = $message->getChildren();

        array_walk($children, [$this, 'convert']);
    }

    /**
     * @param \Swift_Mime_SimpleMimeEntity $message
     */
    private function convert(\Swift_Mime_SimpleMimeEntity $message)
    {
        if ($message->getContentType() !== 'text/plain') {
            $body = $this->inliner->convert($message->getBody());
            $dom = new Crawler($body);
            $dom->filter('style')->each(function (Crawler $crawler) {
                foreach ($crawler as $node) {
                    $node->parentNode->removeChild($node);
                }
            });
            $message->setBody($dom->html());
        }
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
    }
}
