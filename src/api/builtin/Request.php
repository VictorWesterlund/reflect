<?php

	namespace Reflect;

	use Reflect\Path;
	use Reflect\Database\Database;

	require_once Path::reflect("src/database/Database.php");

	class Request extends Database {
		public static function get_api_key(): ?string {
			return parent::get_key_from_request();
		}
	}