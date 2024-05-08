<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Controller;
	use Reflect\Database\Models\Groups\GroupsModel;

	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Groups.php");

	class GET_ReflectGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(GroupsModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(GroupsModel::ACTIVE->value))
					->type(Type::BOOLEAN),

				(new Rules(GroupsModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			return parent::return_list_response(
				$this->for(GroupsModel::TABLE)
				->where($_GET)
				->select([
					GroupsModel::ID->value,
					GroupsModel::ACTIVE->value,
					GroupsModel::CREATED->value
				])
			);
		}
	}