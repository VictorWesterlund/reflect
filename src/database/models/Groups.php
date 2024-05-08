<?php

	namespace Reflect\Database\Models\Groups;

	enum GroupsModel: string {		
		const TABLE = "groups";

		case ID      = "id";
		case ACTIVE  = "active";
		case CREATED = "date_created";
	}