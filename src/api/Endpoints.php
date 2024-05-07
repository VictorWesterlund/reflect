<?php

	namespace Reflect\API;

	use Reflect\ENV;

	enum Endpoints: string {
		case ACL          = "acl";
		case KEYS         = "keys";
		case USERS        = "users";
		case GROUPS       = "groups";
		case ENDPOINTS    = "endpoints";
		case SESSION_KEY  = "session/key";
		case SESSION_USER = "session/user";

		// Prepend configured Reflect internal-api prefix to Endpoint
		public function endpoint(): string {
			// Get configured Reflect internal-api prefix
			$prefix = ENV::get(ENV::INTERNAL_REQUEST_PREFIX);

			// Append tailing slash if absent
			$prefix .= substr($prefix, -1) === "/" ? "" : "/";

			return $prefix . $this->value;
		}
	}