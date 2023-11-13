<?php

	namespace Reflect\Database\Acl;

	use \victorwesterlund\xEnum;

	enum Model: string {
		use xEnum;
		
		const TABLE = "api_acl";

		case ID       = "id";
		case API_KEY  = "api_key";
		case ENDPOINT = "endpoint";
		case METHOD   = "method";
		case CREATED  = "created";
	}