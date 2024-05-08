<?php

	namespace Reflect;

	use Reflect\Path;
	use Reflect\Database\Database;

	require_once Path::reflect("src/database/Database.php");

	class Request extends Database {
		public static function get_api_key(): ?string {
			return parent::$api_key;
		}

		public static function get_user(): ?string {
			return parent::$user_id;
		}

		public static function get_user_groups(): array {
			return parent::$user_groups;
		}
	}