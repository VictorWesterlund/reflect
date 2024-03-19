<?php

	namespace Reflect\Database\Model\RelUsersGroups;

	enum RelUsersGroupsModel: string {		
		const TABLE = "rel_users_groups";

		case REF_USER  = "ref_user";
		case REF_GROUP = "ref_group";
	}