<?php

namespace Multiple;

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
		$di->set('dispatcher', function () {
			$dispatcher = new \Phalcon\Mvc\Dispatcher();

			//Attach a event listener to the dispatcher
			$eventManager = new \Phalcon\Events\Manager();
			$eventManager->attach('dispatch', new \AclTracking('frontend'));

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