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

	class POST_ReflectGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(GroupsModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
					->default(parent::gen_uuid4()),

				(new Rules(GroupsModel::ACTIVE->value))
					->type(Type::BOOLEAN)
					->default(true),

				(new Rules(GroupsModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
					->default(time())
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
			// Bail out if group with id already exist
			if ($this->group_exists()) {
				return new Response("Failed to create group with id '{$_POST[GroupsModel::ID->value]}'. Group already exist", 409);
			}

			return $this->for(GroupsModel::TABLE)
				->insert($_POST)
				? new Response($_POST[GroupsModel::ID->value], 201)
				: new Response(self::error_prefix(), 500);
		}
	}