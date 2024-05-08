<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Controller;
	use Reflect\Database\Models\Keys\KeysModel;

	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Keys.php");

	class GET_ReflectKeys extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(KeysModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(KeysModel::ACTIVE->value))
					->type(Type::BOOLEAN),

				(new Rules(KeysModel::REF_USER->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

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

		public function main(): Response {
			return parent::return_list_response(
				$this->for(KeysModel::TABLE)
				->where($_GET)
				->select([
					KeysModel::ID->value,
					KeysModel::ACTIVE->value,
					KeysModel::REF_USER->value,
					KeysModel::EXPIRES->value,
					KeysModel::CREATED->value
				])
			);
		}
	}