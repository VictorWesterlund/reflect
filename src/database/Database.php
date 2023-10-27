<?php

	namespace Reflect\Database;

	use \Reflect\ENV;

	use \libmysqldriver\MySQL;

	// Open connection to MySQL with credentials
	class Database extends MySQL {
		public function __construct() {
			parent::__construct(
                ENV::get("mysql_host"),
                ENV::get("mysql_user"),
                ENV::get("mysql_pass"),
                ENV::get("mysql_db")
            );
		}

		// Return columns that exist in both $fields and $model
		public static function filter_columns(array $fields, array $model): array {
			return array_filter($fields, fn($key) => in_array($key, $model) && !is_null($fields[$key]), ARRAY_FILTER_USE_KEY);
		}
	}