<?php

	namespace Reflect\Database\Models\UsersGroups;

	enum UsersGroupsModel: string {		
		const TABLE = "rel_users_groups";

		case REF_USER  = "ref_user";
		case REF_GROUP = "ref_group";
	}