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

	class POST_ReflectEndpoints extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(EndpointsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(EndpointsModel::ACTIVE->value))
					->type(Type::BOOLEAN)
					->default(true)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if an API key exists with the provided id
		private function endpoint_exists(): bool {
			return (new Call(Endpoints::ENDPOINTS->endpoint()))
				->params([EndpointsModel::ID->value => $_POST[EndpointsModel::ID->value]])
				->get()->ok;
		}

		public function main(): Response {
			// Bail out if endpoint with id already exist
			if ($this->endpoint_exists()) {
				return new Response("Failed to create endpoint with id '{$_POST[EndpointsModel::ID->value]}'. Endpoint already exist", 409);
			}

			return $this->for(EndpointsModel::TABLE)
				->insert($_POST)
				? new Response($_POST[EndpointsModel::ID->value], 201)
				: new Response(self::error_prefix(), 500);
		}
	}