<?php

	namespace Reflect;

	/*
		This file contains all the core features of Reflect.
		Everything here is loaded before endpoint request processing begins.
	*/

	/*
		# Default endpoint interface
		This interface need to be implemented by all endpoints
	*/
	interface Endpoint {
        public function main();
    }


	/*
		# Reflect environment abstractions
		This class contains abstractions for Reflect environment variables
	*/
	class ENV {
        // Reflect environment variables are placed in $_ENV as an assoc array with this as the array key.
        // Example: $_ENV[self::NS][<reflect_env_var>]
        private const NS = "_REFLECT";

		// Name of the .ini file containing environment variables to be loaded (internal and userspace)
		private const INI = ".env.ini";

		// Path to the composer autoload file (internal and userspace)
		private const COMPOSER_AUTLOAD = "vendor/autoload.php";

        // Returns true if Reflect environment variable is present and not empty in 
        public static function isset(string $key): bool {
            return in_array($key, array_keys($_ENV[self::NS])) && !empty($_ENV[self::NS][$key]);
        }

		// Get environment variable by key
		public static function get(string $key): mixed {
			return self::isset($key) ? $_ENV[self::NS][$key] : null;
		}

		// Set environment variable key, value pair
		public static function set(string $key, mixed $value = null) {
			$_ENV[self::NS][$key] = $value;
		}

		/* ---- */

		// Load environment variables and dependancies
		public static function init() {
			// Initialize namespaced environment variables from .ini config file
			$_ENV[self::NS] = parse_ini_file(Path::reflect(self::INI), true) ?? die("Environment variable file '" . self::INI . "' not found");

			require_once Path::reflect(self::COMPOSER_AUTLOAD) ?? die("Failed to load dependencies. Install dependencies with 'composer install'");

			// Merge environment variables from userspace if present
			if (file_exists(Path::root(self::INI))) {
				$_ENV = array_merge($_ENV, parse_ini_file(Path::root(self::INI), true));
			}

			// Load composer dependencies from userspace if exists
			if (file_exists(Path::root(self::COMPOSER_AUTLOAD))) {
				require_once Path::root(self::COMPOSER_AUTLOAD);
			}
		}
    }

	/* 
		# Path abstractions
		These methods return paths to various files and folders.
		A tailing "/" is appended to each path to prevent peer dirname attacks from endpoints.
	*/
	class Path {
		// Get path to or relative path from the Reflect install directory
		public static function reflect(string $crumbs = ""): string {
			return dirname(__DIR__) . "/" . $crumbs;
		}

		// Get path to the default API class
		public static function init(): string {
			return (__CLASS__)::reflect("src/api/API.php");
		}

		// Get path to or relative path from the user's configured root
		public static function root(string $crumbs = ""): string {
			return ENV::get("endpoints") . (substr($crumbs, 0, 1) === "/" ? "" : "/") . $crumbs;
		}
	}

	// Load config and dependencies
	ENV::init();