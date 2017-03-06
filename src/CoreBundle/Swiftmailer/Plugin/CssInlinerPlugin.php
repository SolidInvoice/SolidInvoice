<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Swiftmailer\Plugin;

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
     * @param \Swift_Mime_MimeEntity $message
     */
    private function convert(\Swift_Mime_MimeEntity $message)
    {
	if ($message->getContentType() !== 'text/plain') {
	    $message->setBody($this->inliner->convert($message->getBody()));
	}
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
    }
}
