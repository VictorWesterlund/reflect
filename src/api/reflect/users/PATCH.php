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

	class PATCH_ReflectUsers extends Controller implements Endpoint {
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

			$this->ruleset->POST([
				(new Rules(UsersModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(UsersModel::ACTIVE->value))
					->type(Type::BOOLEAN),

				(new Rules(UsersModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if a user exists with the provided id
		private function user_exists(string $id): bool {
			return (new Call(Endpoints::USERS->endpoint()))
				->params([UsersModel::ID->value => $id])
				->get()->ok;
		}

		public function main(): Response {
			// Bail out as there is nothing to do with an empty request body
			if (empty($_POST)) {
				return new Response($_GET[UsersModel::ID->value]);
			}

			// Can not update entity for nonexistent user id
			if (!$this->user_exists($_GET[UsersModel::ID->value])) {
				return new Response("Failed to update user with id '{$_GET[UsersModel::ID->value]}'. User does not exist", 404);
			}

			// Verify that an updated user id does not already exist
			if (array_key_exists(UsersModel::ID->value, $_POST)) {
				if ($this->user_exists($_POST[UsersModel::ID->value])) {
					return new Response("Failed to update user with id '{$_GET[UsersModel::ID->value]}'. User with id '{$_POST[UsersModel::ID->value]}' already exist", 409);
				}
			}

			return $this->for(UsersModel::TABLE)
				->where([UsersModel::ID->value => $_GET[UsersModel::ID->value]])
				->update($_POST)
					// Return updated or existing user id if successful
					? new Response($_POST[UsersModel::ID->value] ?? $_GET[UsersModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}