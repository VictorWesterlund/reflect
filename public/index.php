<?php
	
	require_once "../src/Init.php";

	// Do request routing
	require_once Path::reflect("src/request/Router.php");
	(new Router(ConType::HTTP))->main();