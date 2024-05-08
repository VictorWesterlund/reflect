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

	class DELETE_ReflectEndpoints extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(EndpointsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if an endpoint exists with the provided id
		private function endpoint_exists(): bool {
			return (new Call(Endpoints::ENDPOINTS->endpoint()))
				->params([EndpointsModel::ID->value => $_POST[EndpointsModel::ID->value]])
				->get()->ok;
		}

		public function main(): Response {
			// Can not update entity for nonexistent user id
			if (!$this->endpoint_exists()) {
				return new Response("Failed to delete endpoint with id '{$_POST[EndpointsModel::ID->value]}}'. Endpoint does not exist", 404);
			}

			return $this->for(EndpointsModel::TABLE)
				->where([EndpointsModel::ID->value => $_POST[EndpointsModel::ID->value]])
				->delete()
					// Return user id that was deleted if successful
					? new Response($_POST[EndpointsModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}