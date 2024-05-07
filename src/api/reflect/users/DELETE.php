<?php

	use Reflect\Call;
	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Endpoints;
	use Reflect\API\Controller;
	use Reflect\Database\Models\Users\UsersModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Users.php");

	class DELETE_ReflectUsers extends Controller implements Endpoint {
		private const PREFIX_ERROR = "Failed to delete Reflect API user with id '%s'";

		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

            $this->ruleset->GET([
				(new Rules(UsersModel::ID->value))
                    ->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		private static function error_prefix(): string {
			return sprintf(self::PREFIX_ERROR, $_GET[UsersModel::ID->value]);
		}

		// Returns true if a user exists with the provided id
		private function user_exists(): bool {
			return (new Call(Endpoints::USERS->endpoint()))
				->params([UsersModel::ID->value => $_GET[UsersModel::ID->value]])
				->get()->ok;
		}

		// Update an existing Reflect API user entity abd return updated/existing user id on success
		public function main(): Response {
			// Can not update entity for nonexistent user id
			if (!$this->user_exists()) {
				return new Response(self::error_prefix() . ". User does not exist");
			}

			return $this->for(UsersModel::TABLE)
                ->where([UsersModel::ID->value => $_GET[UsersModel::ID->value]])
                ->delete()
                    // Return user id that was deleted if successful
                    ? new Response($_GET[UsersModel::ID->value])
                    : new Response(self::error_prefix(), 500);
		}
	}