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

		// Get path to user endpoints
		public static function endpoints(string $crumbs = ""): string {
			$endpoints = !empty($_ENV["endpoints"]) ? $_ENV["endpoints"] : Path::root("endpoints");
			return $endpoints . "/" . $crumbs;
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

	// Put environment variables from INI into superglobal
	$_ENV = parse_ini_file(Path::root(".env.ini"), true);
	
	// Merge environment variables from user endpoints with existing
	if (file_exists(Path::endpoints(".env.ini"))) {
		$_ENV = array_merge($_ENV, parse_ini_file(Path::endpoints(".env.ini"), true));
	}