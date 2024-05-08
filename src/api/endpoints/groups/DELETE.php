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
	use Reflect\Database\Models\Groups\GroupsModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Groups.php");

	class DELETE_ReflectGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(GroupsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if a user exists with the provided id
		private function group_exists(): bool {
			return (new Call(Endpoints::GROUPS->endpoint()))
				->params([GroupsModel::ID->value => $_POST[GroupsModel::ID->value]])
				->get()->ok;
		}

		public function main(): Response {
			// Can not update entity for nonexistent user id
			if (!$this->group_exists()) {
				return new Response("Failed to delete group with id '{$_POST[GroupsModel::ID->value]}'. Group does not exist", 404);
			}

			return $this->for(GroupsModel::TABLE)
				->where([GroupsModel::ID->value => $_POST[GroupsModel::ID->value]])
				->delete()
					// Return user id that was deleted if successful
					? new Response($_POST[GroupsModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}