<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Controller;
	use Reflect\Database\Models\Endpoints\EndpointsModel;

	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Endpoints.php");

	class GET_ReflectEndpoints extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(EndpointsModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(EndpointsModel::ACTIVE->value))
					->type(Type::BOOLEAN)
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			return parent::return_list_response(
				$this->for(EndpointsModel::TABLE)
				->where($_GET)
				->select([
					EndpointsModel::ID->value,
					EndpointsModel::ACTIVE->value
				])
			);
		}
	}