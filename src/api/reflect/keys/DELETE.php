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

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Keys.php");

	class DELETE_ReflectKeys extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(KeysModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if a key exists with the provided id
		private function key_exists(): bool {
			return (new Call(Endpoints::KEYS->endpoint()))
				->params([KeysModel::ID->value => $_POST[KeysModel::ID->value]])
				->get()->ok;
		}

		public function main(): Response {
			// Can not update entity for nonexistent key id
			if (!$this->key_exists()) {
				return new Response("Failed to delete key with id '{$_POST[KeysModel::ID->value]}'. Key does not exist", 404);
			}

			return $this->for(KeysModel::TABLE)
				->where([KeysModel::ID->value => $_POST[KeysModel::ID->value]])
				->delete()
					// Return key id that was deleted if successful
					? new Response($_POST[KeysModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}