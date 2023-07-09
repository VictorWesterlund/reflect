<?php
	
	use \Reflect\Path;
	use \Reflect\Request\Router;
	use \Reflect\Request\Connection;

	require_once "../src/Init.php";

	// Do request routing
	require_once Path::reflect("src/request/Router.php");
	(new Router(Connection::HTTP))->main();