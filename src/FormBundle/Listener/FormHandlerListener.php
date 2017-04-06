<?php

declare(strict_types=1);

/*
 * This file is part of the CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\FormBundle\Listener;

use CSBill\FormBundle\Handler\FormCollectionHandler;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\ProxyInterface;
use ProxyManager\Proxy\VirtualProxyInterface;
use SolidWorx\FormHandler\Event\FormHandlerEvent;
use SolidWorx\FormHandler\Event\FormHandlerEvents;
use SolidWorx\FormHandler\FormCollection;
use SolidWorx\FormHandler\FormHandlerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;

class FormHandlerListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
	return [
	    //'form_handler.form' => 'onForm',
	    //FormHandlerEvents::EVENT_FORM_SUCCESS => ['onSuccess', 150]
	];
    }

    public function onForm(Event $event)
    {
	$handler = $event->getHandler();

	if (!$handler instanceof FormCollectionHandler) {
	    return;
	}

	$factory = new Factory();

	if ($handler instanceof VirtualProxyInterface && !$handler->isProxyInitialized()) {
	    $handler->initializeProxy();
	    $handler = $handler->getWrappedValueHolderValue();
	}

	$c = new class() {
	    private $formData;

	    public function __invoke(ProxyInterface $proxy, FormHandlerInterface $handler, string $method, array $arguments) {
		switch ($method) {
		    case 'getForm':
			$this->formData = FormCollection::getEntityCollections($arguments['options'][0] ?? null);
			break;
		    case 'onSuccess':
			break;
		}
	    }
	};

	$proxy = $factory->createProxy(
	    $handler,
	    ['getForm' => $c]
	//['getForm' => function () { echo "PostFoo!\n"; }]
	//[]//['onSuccess' => function () { echo "PostFoo!\n"; exit; }]
	);

	$event->setHandler($proxy);
    }

    public function onSuccess(FormHandlerEvent $event)
    {
	$handler = $event->getHandler();

	if (!$handler instanceof FormCollectionHandler) {
	    return;
	}


    }
}