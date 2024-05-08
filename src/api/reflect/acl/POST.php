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
	use Reflect\Database\Models\Acl\AclModel;
	use Reflect\Database\Models\Acl\MethodEnum;
	use Reflect\Database\Models\Groups\GroupsModel;
	use Reflect\Database\Models\Endpoints\EndpointsModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Acl.php");
	require_once Path::reflect("src/database/models/Groups.php");
	require_once Path::reflect("src/database/models/Endpoints.php");

	class POST_ReflectAcl extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(AclModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
					->default(parent::gen_uuid4()),

				(new Rules(AclModel::REF_GROUP->value))
					->type(Type::NULL)
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
					->default(null),

				(new Rules(AclModel::REF_ENDPOINT->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(AclModel::METHOD->value))
					->required()
					->type(Type::ENUM, array_column(MethodEnum::cases(), "name")),

				(new Rules(AclModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
					->default(time())
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if an endpoint by id exists
		private function endpoint_exists(): bool {
			return (new Call(Endpoints::ENDPOINTS->endpoint()))
				->params([EndpointsModel::ID->value => $_POST[AclModel::REF_ENDPOINT->value]])
				->get()->ok;
		}

		// Returns true if a group by id exists
		private function group_exists(): bool {
			return (new Call(Endpoints::GROUPS->endpoint()))
				->params([GroupsModel::ID->value => $_POST[AclModel::REF_GROUP->value]])
				->get()->ok;
		}

		// Returns true if an ACL rule with the requested composition already exist
		private function acl_rule_exists(): bool {
			return (new Call(Endpoints::ACL->endpoint()))
				->params([
					AclModel::REF_ENDPOINT->value => $_POST[AclModel::REF_ENDPOINT->value],
					AclModel::REF_GROUP->value    => $_POST[AclModel::REF_GROUP->value],
					AclModel::METHOD->value       => $_POST[AclModel::METHOD->value]
				])
				->get()->ok;
		}

		public function main(): Response {
			// Can not create ACL rule for nonexistent endpoint
			if (!$this->endpoint_exists()) {
				return new Response("Failed to create ACL rule. Endpoint with id '{$_POST[AclModel::REF_ENDPOINT->value]}' does not exist", 409);
			}

			// Can not create ACL rule for nonexistent group
			if ($_POST[AclModel::REF_GROUP->value] !== null && !$this->group_exists()) {
				return new Response("Failed to create ACL rule. Group with id '{$_POST[AclModel::REF_GROUP->value]}' does not exist", 409);
			}

			// Bail out if ACL rule already exist
			if ($this->acl_rule_exists()) {
				return new Response("Failed to create ACL rule. Rule already exist", 409);
			}

			return $this->for(AclModel::TABLE)
				->insert($_POST)
				? new Response($_POST[AclModel::ID->value], 201)
				: new Response(self::error_prefix(), 500);
		}
	}