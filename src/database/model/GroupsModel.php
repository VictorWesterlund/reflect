<?php

	namespace Reflect\Database\Model\Groups;

	enum Model: string {		
		const TABLE = "groups";

		case ID      = "id";
		case ACTIVE  = "active";
		case CREATED = "created";
	}