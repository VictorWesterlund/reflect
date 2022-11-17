<?php

	// Return path to locations within this project.
	// A tailing / is appended to each return to prevent adjacent dirname
	// attacks from API controllers.
	final class Path {
		// Get path to /src/ folder
		public static function src(string $crumbs = ""): string {
			return __DIR__ . "/" . $crumbs;
		}

		// Get path to root of project
		public static function root(string $crumbs = ""): string {
			return dirname(Path::src()) . "/" . $crumbs;
		}

		// Get path to API parent
		public static function api(): string {
			return Path::src("api/API.php");
		}
	}

	final class JSON {
		// Parse JSON from file as assoc array
		public static function load(string $path): array|null {
			return json_decode(file_get_contents($path), true) ?? null;
		}
	}

	// Database classes need to export these methods to allow interfaces
	// to swap between drivers with minimal to no code changes.
	interface DatabaseDriver {
		public function return_array(string $sql, mixed $params = []): array;
		public function return_bool(string $sql, mixed $params = []): bool;
	}

	// Load Composer dependencies
	//require_once Path::root("vendor/autoload.php");

	// Put environment variables from INI into superglobal
	$_ENV = parse_ini_file(Path::root(".env.ini"), true);