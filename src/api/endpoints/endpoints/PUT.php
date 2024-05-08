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
	use Reflect\Database\Models\Endpoints\EndpointsModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Endpoints.php");

	class PUT_ReflectEndpoints extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(EndpointsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);

			$this->ruleset->POST([
				(new Rules(EndpointsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(EndpointsModel::ACTIVE->value))
					->required()
					->type(Type::BOOLEAN)
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			// Use the PATCH endpoint to PUT all values for entity by id
			return (new Call(Endpoints::ENDPOINTS->endpoint()))
				->params([EndpointsModel::ID->value => $_GET[EndpointsModel::ID->value]])
				->patch($_POST);
		}
	}