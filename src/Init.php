<?php

	// Return path to locations within this project.
	// A tailing / is appended to each return to prevent adjacent dirname
	// attacks from API controllers.
	final class Path {
		// Get path to or relative path from the Reflect install directory
		public static function reflect(string $crumbs = ""): string {
			return dirname(__DIR__) . "/" . $crumbs;
		}

		// Get path to the default API class
		public static function init(): string {
			return Path::reflect("src/api/API.php");
		}

		// Get path to or relative path from the user's configured root
		public static function root(string $crumbs = ""): string {
			return $_ENV["endpoints"] . (substr($crumbs, 0, 1) === "/" ? "" : "/") . $crumbs;
		}
	}

	final class JSON {
		// Parse JSON from file as assoc array
		public static function load(string $path): array|null {
			return json_decode(file_get_contents($path), true) ?? null;
		}
	}

	// Put environment variables from INI into superglobal
	$_ENV = parse_ini_file(Path::reflect(".env.ini"), true);
	
	// Merge environment variables from user endpoints with existing
	if (file_exists(Path::root(".env.ini"))) {
		$_ENV = array_merge($_ENV, parse_ini_file(Path::root(".env.ini"), true));
	}

	// Load composer dependencies from userspace if exists
	if (file_exists(Path::root("vendor/autoload.php"))) {
		require_once Path::root("vendor/autoload.php");
	}