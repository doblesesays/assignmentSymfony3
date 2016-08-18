<?php

namespace Genessis\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSuscriberInterface;

class LocaleListener extends EventSuscriberInterface
{
	private = $defaultLocale;
	
	function __construct($defaultLocale = 'en')
	{
		$this->defaultLocale = $defaultLocale;
	}

	public function onKernelRequest(GetResponseEvent $event)
	{
		$request = $event->getRequest();
		if (!$request->hasPreviousSession()){
			return;
		}
	}


	if ($locale = $request->attributes->get('_locale')) {
		$request->getSession()->set('_locale', $locale);
		//aqui deberia actualizar la entidad:user:locale
	}else{
		$request->setlocale($request->getSession()->get('_locale' , $this->defaultLocale));
	}

	public static function getSuscribedEvents()
	{
		return array(
			KernelEvents::REQUEST => array(array('onKernelRequest', 15)),
		);
	}
}