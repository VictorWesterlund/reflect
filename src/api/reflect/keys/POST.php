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
	use Reflect\Database\Models\Keys\KeysModel;
	use Reflect\Database\Models\Users\UsersModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Keys.php");
	require_once Path::reflect("src/database/models/Users.php");

	class POST_ReflectKeys extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(KeysModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
					->default(parent::gen_uuid4()),

				(new Rules(KeysModel::ACTIVE->value))
					->type(Type::BOOLEAN)
					->default(true),

				(new Rules(KeysModel::REF_USER->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(KeysModel::EXPIRES->value))
					->type(Type::NULL)
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
					->default(null),

				(new Rules(KeysModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
					->default(time())
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if an API key exists with the provided id
		private function user_exists(): bool {
			return (new Call(Endpoints::USERS->endpoint()))
				->params([UsersModel::ID->value => $_POST[KeysModel::REF_USER->value]])
				->get()->ok;
		}

		// Returns true if a key exists with the provided id
		private function key_exists(): bool {
			return (new Call(Endpoints::KEYS->endpoint()))
				->params([KeysModel::ID->value => $_POST[KeysModel::ID->value]])
				->get()->ok;
		}

		public function main(): Response {
			// Bail out if key with id already exist
			if ($this->key_exists()) {
				return new Response("Failed to create key with id '{$_POST[KeysModel::ID->value]}}'. Key already exist", 409);
			}

			// Verify that the requested user exist if defined
			if ($_POST[KeysModel::REF_USER->value]) {
				if (!$this->user_exists()) {
					return new Response("Failed to create key with id '{$_POST[KeysModel::ID->value]}}'. User with id '{$_POST[KeysModel::REF_USER->value]}' does not exist", 404);
				}
			}

			return $this->for(KeysModel::TABLE)
				->insert($_POST)
				? new Response($_POST[KeysModel::ID->value], 201)
				: new Response(self::error_prefix(), 500);
		}
	}