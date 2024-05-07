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

	class POST_ReflectUsers extends Controller implements Endpoint {
		private const PREFIX_ERROR = "Failed to create Reflect API user with id '%s'";

		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(UsersModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
                    ->default(parent::gen_uuid4()),

				(new Rules(UsersModel::ACTIVE->value))
					->type(Type::BOOLEAN)
                    ->default(true),

				(new Rules(UsersModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
                    ->default(time())
			]);
			
			parent::__construct($this->ruleset);
		}

		private static function error_prefix(): string {
			return sprintf(self::PREFIX_ERROR, $_POST[UsersModel::ID->value]);
		}

		// Returns true if a user exists with the provided id
		private function user_exists(): bool {
			return (new Call(Endpoints::USERS->endpoint()))
				->params([UsersModel::ID->value => $_POST[UsersModel::ID->value]])
				->get()->ok;
		}

		// Create new Reflect API user from request body parameteres and return user id if successful
		public function main(): Response {
			// Can not create duplicate user ids
			if ($this->user_exists()) {
				return new Response(self::error_prefix() . ". User already exist");
			}

			return $this->for(UsersModel::TABLE)
				->insert($_POST)
                ? new Response($_POST[UsersModel::ID->value])
                : new Response(self::error_prefix(), 500);
		}
	}