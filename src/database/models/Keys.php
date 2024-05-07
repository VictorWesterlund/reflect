<?php

	namespace Reflect\Database\Models\Keys;

	enum KeysModel: string {
		const TABLE = "keys";

		case ID       = "id";
		case ACTIVE   = "active";
		case REF_USER = "ref_user";
		case EXPIRES  = "expires";
		case CREATED  = "created";
	}