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

	class PATCH_ReflectKeys extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(KeysModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);

			$this->ruleset->POST([
				(new Rules(KeysModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(KeysModel::ACTIVE->value))
					->type(Type::BOOLEAN),

				(new Rules(KeysModel::REF_USER->value))
					->type(Type::STRING)
					->min(1),

				(new Rules(KeysModel::EXPIRES->value))
					->type(Type::NULL)
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE),

				(new Rules(KeysModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
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
		private function key_exists(string $id): bool {
			return (new Call(Endpoints::KEYS->endpoint()))
				->params([KeysModel::ID->value => $id])
				->get()->ok;
		}

		public function main(): Response {
			// Bail out as there is nothing to do with an empty request body
			if (empty($_POST)) {
				return new Response($_GET[KeysModel::ID->value]);
			}

			// Can not update entity for nonexistent key id
			if (!$this->key_exists($_GET[KeysModel::ID->value])) {
				return new Response("Failed to update key with id '{$_POST[KeysModel::ID->value]}'. Key does not exist", 404);
			}

			// Can't set key id to a key id that already exist
			if (array_key_exists(KeysModel::ID->value, $_POST)) {
				if ($this->key_exists($_POST[KeysModel::ID->value])) {
					return new Response("Failed to update key with id '{$_POST[KeysModel::ID->value]}'. Key with id '{$_POST[KeysModel::ID->value]}' already exist", 409);
				}
			}

			// Can't set ref_user to a user id that does not exist
			if (array_key_exists(KeysModel::REF_USER->value, $_POST)) {
				if (!$this->user_exists()) {
					return new Response("Failed to update key with id '{$_POST[KeysModel::ID->value]}'. User with id '{$_POST[KeysModel::REF_USER->value]}' does not exist", 404);
				}
			}

			return $this->for(KeysModel::TABLE)
				->where([KeysModel::ID->value => $_GET[KeysModel::ID->value]])
				->update($_POST)
					// Return updated or existing key id if successful
					? new Response($_POST[KeysModel::ID->value] ?? $_GET[KeysModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}