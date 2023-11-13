<?php

	namespace Reflect\Database\Users;

	use \victorwesterlund\xEnum;

	enum Model: string {
		use xEnum;
		
		const TABLE = "api_users";

		case ID      = "id";
		case ACTIVE  = "active";
		case CREATED = "created";
	}