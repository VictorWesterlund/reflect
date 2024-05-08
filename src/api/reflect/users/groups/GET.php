<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Controller;
	use Reflect\Database\Models\UsersGroups\UsersGroupsModel;

	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/UsersGroups.php");

	class GET_ReflectUsersGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(UsersGroupsModel::REF_USER->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(UsersGroupsModel::REF_GROUP->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			return parent::return_list_response(
				$this->for(UsersGroupsModel::TABLE)
				->where($_GET)
				->select([
					UsersGroupsModel::REF_USER->value,
					UsersGroupsModel::REF_GROUP->value
				])
			);
		}
	}