<?php

	namespace Reflect;

	/*
		This file contains all the core features of Reflect.
		Everything here is loaded before endpoint request processing begins.
	*/

	use Reflect\ENV;
	use Reflect\Path;

	enum ENV: string {
		protected const NAMESPACE = "_reflect";
		protected const ENV_INI   = ".env.ini";
		protected const COMPOSER  = "vendor/autoload.php";

		// START User configurable environment variables

		case ENDPOINTS = "endpoints";

		case MYSQL_HOST = "mysql_host";
		case MYSQL_USER = "mysql_user";
		case MYSQL_PASS = "mysql_pass";
		case MYSQL_DB   = "mysql_db";

		case INTERNAL_REQUEST_PREFIX = "internal_request_prefix";

		// END User configurable environment variables

		case INTERNAL_STDOUT = "internal_stdout";

		// Returns true if Reflect environment variable is present and not empty in 
		public static function isset(ENV $key): bool {
			return in_array($key->value, array_keys($_ENV[self::NAMESPACE])) && !empty($_ENV[self::NAMESPACE][$key->value]);
		}

		// Get environment variable by key
		public static function get(ENV $key): mixed {
			return self::isset($key) ? $_ENV[self::NAMESPACE][$key->value] : null;
		}

		// Set environment variable key, value pair
		public static function set(ENV $key, mixed $value = null) {
			$_ENV[self::NAMESPACE][$key->value] = $value;
		}

		// Load environment variables and dependancies
		public static function init() {
			// Put environment variables from Vegvisir .ini into namespaced superglobal
			$_ENV[self::NAMESPACE] = parse_ini_file(Path::reflect(self::ENV_INI), true);

			// Don't perform loopback responses by default
			ENV::set(ENV::INTERNAL_STDOUT, false);

			// Load Composer dependencies
			require_once Path::reflect(self::COMPOSER);

			// Merge environment variables from user site into superglobal
			if (file_exists(Path::root(self::ENV_INI))) {
				$_ENV = array_merge($_ENV, parse_ini_file(Path::root(self::ENV_INI), true));
			}

			// Load composer dependencies from userspace if exists
			if (file_exists(Path::root(self::COMPOSER))) {
				require_once Path::root(self::COMPOSER);
			}
		}
	}

	/* 
		# Path abstractions
		These methods return paths to various files and folders.
		A tailing "/" is appended to each path to prevent peer dirname attacks from endpoints.
	*/
	class Path {
		const ENDPOINTS_FOLDER = "endpoints";

		// Get path to or relative path from the Reflect install directory
		public static function reflect(string $crumbs = ""): string {
			return dirname(__DIR__) . "/" . $crumbs;
		}

		// Get path to or relative path from the user's configured root
		public static function root(string $crumbs = ""): string {
			return ENV::get(ENV::ENDPOINTS) . (substr($crumbs, 0, 1) === "/" ? "" : "/") . $crumbs;
		}
	}

	// Load config and dependencies
	ENV::init();