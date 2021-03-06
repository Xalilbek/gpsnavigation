<?php

namespace Multiple;

use Lib\Auth;
use Lib\AuthPanel;
use Lib\Lib;
use Lib\Odds;
use Lib\Parameters;
use Lib\Translation;

class Module
{

	public function registerAutoloaders()
	{
		$loader = new \Phalcon\Loader();

		$loader->registerNamespaces(array(
			'Controllers' => '../modules/frontend/controllers/',
		));

		$loader->register();
	}

	public function registerServices($di)
	{
		$di->set('lang', function ()
		{
			$translation = new Translation();
			$translation->init(3);
			return $translation;
		});

		$di->set('lib', function ()
		{
			$class = new Lib();
			return $class;
		});

		$di->set('auth', function ()
		{
			$class = new AuthPanel();
			return $class;
		});

		$di->set('parameters', function ()
		{
			$class = new Parameters();
			return $class;
		});


		$di->set('odd', function ()
		{
			$class = new Odds();
			return $class;
		});


		$di->set('dispatcher', function () {
			$dispatcher = new \Phalcon\Mvc\Dispatcher();

			//Attach a event listener to the dispatcher
			$eventManager = new \Phalcon\Events\Manager();
			$eventManager->attach('dispatch', new \AclPanel('frontend'));

			$dispatcher->setEventsManager($eventManager);
			$dispatcher->setDefaultNamespace("Controllers");
			return $dispatcher;
		});

		$di->set('view', function () {
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir('../modules/frontend/views/');
			return $view;
		});

	}

}