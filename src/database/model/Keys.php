<?php

	namespace Reflect\Database\Keys;

	use \victorwesterlund\xEnum;

	enum Model: string {
		use xEnum;
		
		const TABLE = "api_keys";

		case ID      = "id";
		case ACTIVE  = "active";
		case USER    = "user";
		case EXPIRES = "expires";
		case CREATED = "created";
	}