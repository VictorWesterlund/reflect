<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Controller;
	use Reflect\Database\Models\Users\UsersModel;

	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Users.php");

	class GET_ReflectUsers extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
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

		// Return assoc array of all Reflect API users with optional search parameter filters by column
		public function main(): Response {
			return parent::return_list_response(
				$this->for(UsersModel::TABLE)
				->where($_GET)
				->select([
					UsersModel::ID->value,
					UsersModel::ACTIVE->value,
					UsersModel::CREATED->value
				])
			);
		}
	}