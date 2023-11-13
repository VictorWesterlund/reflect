<?php

	namespace Reflect\Database\Endpoints;

	use \victorwesterlund\xEnum;

	enum Model: string {
		use xEnum;
		
		const TABLE = "api_endpoints";

		case ID     = "endpoint";
		case ACTIVE = "active";
	}