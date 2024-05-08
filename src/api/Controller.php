<?php

	namespace Reflect\API;

	use \mysqli_result;

	use Reflect\Path;
	use Reflect\Response;
	use ReflectRules\Ruleset;

	use Reflect\Database\Database;

	require_once Path::reflect("src/database/Database.php");

	// Base API controller for all internal Reflect endpoints
	class Controller extends Database {
		public const UUID_LENGTH = 36;

		public function __construct(Ruleset $ruleset = null) {
			// Validate request rules before intializing databse connection
			if ($ruleset) {
				self::validate_ruleset($ruleset);
			}

			parent::__construct();
		}

		// Validate ReflectRules\Ruleset and return Reflect\Response if validation fails
		private static function validate_ruleset(Ruleset $ruleset): true|Response {
			return $ruleset->is_valid() or new Response($ruleset->get_errors(), 422);
		}

		// Generate and return UUID4 string
		public static function gen_uuid4(): string {
			return sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
				mt_rand(0, 0xffff), mt_rand(0, 0xffff),
				mt_rand(0, 0xffff),
				mt_rand(0, 0x0fff) | 0x4000,			
				mt_rand(0, 0x3fff) | 0x8000,
				mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
			);
		}

		// -- Common endpoint return methods --

		// Return all \mysqli_result rows as assoc array if not empty
		public static function return_list_response(mysqli_result $result): Response {
			return $result->num_rows > 0
				? new Response($result->fetch_all(MYSQLI_ASSOC))
				: new Response([], 404);
		}
	}