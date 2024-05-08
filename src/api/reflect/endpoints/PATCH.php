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

	class PATCH_ReflectEndpoints extends Controller implements Endpoint {
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
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(EndpointsModel::ACTIVE->value))
					->type(Type::BOOLEAN)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if an ednpoint exists with the provided id
		private function endpoint_exists(): bool {
			return (new Call(Endpoints::ENDPOINTS->endpoint()))
				->params([EndpointsModel::ID->value => $_POST[EndpointsModel::ID->value]])
				->get()->ok;
		}

		public function main(): Response {
			// Bail out as there is nothing to do with an empty request body
			if (empty($_POST)) {
				return new Response($_GET[EndpointsModel::ID->value]);
			}

			// Can't set endpoint id to an endpoint id that already exist
			if (array_key_exists(EndpointsModel::ID->value, $_POST)) {
				if ($this->endpoint_exists()) {
					return new Response("Failed to update endpoint with id '{$_GET[EndpointsModel::ID->value]}'. Endpoint with id '{$_POST[EndpointsModel::ID->value]}' already exist", 409);
				}
			}

			return $this->for(EndpointsModel::TABLE)
				->where([EndpointsModel::ID->value => $_GET[EndpointsModel::ID->value]])
				->update($_POST)
					// Return updated or existing user id if successful
					? new Response($_POST[EndpointsModel::ID->value] ?? $_GET[EndpointsModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}