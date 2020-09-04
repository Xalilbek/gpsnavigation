<?php
use \Phalcon\Events\Event;
use \Phalcon\Mvc\Dispatcher;

use Models\LogsPanelAccess;
use Lib\MyMongo;

class AclPanel extends \Phalcon\Mvc\User\Component
{
	protected $_module;

	public function __construct($module)
	{
		$this->_module = $module;
	}

	public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
	{
		header("Access-Control-Allow-Origin: *");
		//echo $resource = $this->_module . '-' . $dispatcher->getControllerName(), PHP_EOL; // frontend-dashboard
		//echo $access = $dispatcher->getActionName();

		if(!in_array($dispatcher->getControllerName(), ["index","docs"])) header('Content-type: text/json');

		if(!in_array($dispatcher->getControllerName(), ["auth","register","events","docs","leagues","sports","data"])) {
			$this->auth->init($this->request, $this->lang);

			if (!in_array($dispatcher->getControllerName(), ["tempcoupons","settings"]) && $this->auth->error)
				exit(json_encode(["status" => "error", "error_code" => $this->auth->errorCode, "description" => $this->auth->error], true));
		}

		$vars               = $_REQUEST;
		unset($vars["_url"]);

		$insert = [
			"user_id"       => ($this->auth->getData()) ? (int)$this->auth->getData()->id: 0,
			"url"           => @$_SERVER["REQUEST_URI"],
			"ip"            => @$_SERVER["REMOTE_ADDR"],
			"browser"       => @$_SERVER["HTTP_USER_AGENT"],
			"variables"     => strlen(json_encode($vars, true)) > 1000 ? substr(json_encode($vars, true),0,1000): $vars,
			"created_at"    => LogsPanelAccess::getDate(),
		];
		LogsPanelAccess::insert($insert);
	}
}