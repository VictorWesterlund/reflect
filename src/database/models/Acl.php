<?php

	namespace Reflect\Database\Models\Acl;

	enum MethodEnum {
		case GET;
		case POST;
		case PUT;
		case PATCH;
		case DELETE;
	}

	enum AclModel: string {
		const TABLE = "acl";

		case ID           = "id";
		case REF_GROUP    = "ref_group";
		case REF_ENDPOINT = "ref_endpoint";
		case METHOD       = "method";
		case CREATED      = "created";
	}