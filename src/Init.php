<?php

	namespace Reflect;

	// Environment variables loaded from .env.ini will be stored (array) in $_ENV under this key
	const ENV = "_" . __NAMESPACE__;

	interface Endpoint {
		public function main();
	}

	/* 
		These methods return paths to various files and folders.
		A tailing / is appended to each path to prevent peer dirname attacks from endpoints.
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
			return $_ENV[ENV]["endpoints"] . (substr($crumbs, 0, 1) === "/" ? "" : "/") . $crumbs;
		}
	}

	// Put environment variables from INI into superglobal
	$_ENV[ENV] = parse_ini_file(Path::reflect(".env.ini"), true) ?? die("File '.env.ini' not found");

	require_once Path::reflect("vendor/autoload.php") ?? die("Failed to load dependencies. Install dependencies with 'composer install'");
	
	// Merge environment variables from user endpoints
	if (file_exists(Path::root(".env.ini"))) {
		$_ENV = array_merge($_ENV, parse_ini_file(Path::root(".env.ini"), true));
	}

	// Load composer dependencies from userspace if exists
	if (file_exists(Path::root("vendor/autoload.php"))) {
		require_once Path::root("vendor/autoload.php");
	}