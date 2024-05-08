<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Endpoints;
	use Reflect\API\Controller;
	use Reflect\Database\Models\Acl\AclModel;
	use Reflect\Database\Models\Acl\MethodEnum;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Acl.php");

	class DELETE_ReflectAcl extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(AclModel::REF_GROUP->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(AclModel::REF_ENDPOINT->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(AclModel::METHOD->value))
					->type(Type::ENUM, array_column(MethodEnum::cases(), "name"))
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			return $this->for(AclModel::TABLE)
				->where($_POST)
				->delete()
					// Return user id that was deleted if successful
					? new Response("", 204)
					: new Response(self::error_prefix(), 500);
		}
	}