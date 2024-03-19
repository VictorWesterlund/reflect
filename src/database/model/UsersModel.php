<?php

	namespace Reflect\Database\Model\Users;

	enum UsersModel: string {
		const TABLE = "users";

		case ID      = "id";
		case ACTIVE  = "active";
		case CREATED = "created";
	}