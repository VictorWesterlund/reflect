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

	class PUT_ReflectGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(GroupsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);

			$this->ruleset->POST([
				(new Rules(GroupsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(GroupsModel::ACTIVE->value))
					->required()
					->type(Type::BOOLEAN),

				(new Rules(GroupsModel::CREATED->value))
					->required()
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			// Use the PATCH endpoint to PUT all values for entity by id
			return (new Call(Endpoints::GROUPS->endpoint()))
				->params([GroupsModel::ID->value => $_GET[GroupsModel::ID->value]])
				->patch($_POST);
		}
	}