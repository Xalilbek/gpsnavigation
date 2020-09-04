<?php
error_reporting(1);
ini_set("display_errors", "1");

require '../../settings.php';

class Application extends \Phalcon\Mvc\Application
{
	protected function _registerServices()
	{
        $di = new \Phalcon\DI\FactoryDefault();

		$loader = new \Phalcon\Loader();

        $loader->registerDirs(
            [
                __DIR__ . '/../../../phalcon/acl/'
            ]
        );

        $loader->registerNamespaces(array(
            'Models' 	=> '../../../phalcon/models/',
            //'Plugins' 	=> '../../../phalcon/plugins/',
            'Lib' 		=> '../../../phalcon/library/',
        ))->register();

        define('DEBUG', true);
        if(DEBUG)
        {
            error_reporting(1);
            (new Phalcon\Debug)->listen();
        }

        $di->set('mymongo', function ()
        {
            $mongo = new \Lib\MyMongo();
            return $mongo;
        }, true);

		$di->set('router', function()
        {

			$router = new \Phalcon\Mvc\Router();

			$router->setDefaultModule("frontend");

			$router->add('/:controller/:action/:int', array(
				'module' 		=> 'frontend',
				'controller' 	=> 1,
				'action' 		=> 2,
				'id' 			=> 3,
			));

			$router->add('/:controller/:action', array(
				'module' => 'frontend',
				'controller' => 1,
				'action' => 2,
			));

			$router->add('/:controller', array(
				'module' => 'frontend',
				'controller' => 1,
				'action' => 2,
			));

			$router->add('/{language:[a-z]{2}}', 		array('controller' => "index", 'action' => "index",));

			return $router;

		});

		$this->setDI($di);
	}

	public function main()
	{
		$this->_registerServices();

		$this->registerModules(array(
			'frontend' => array(
				'className' => 'Multiple\Module',
				'path' => '../modules/frontend/Module.php'
			),
		));

		echo $this->handle()->getContent();
	}
}

$application = new Application();
$application->main();
