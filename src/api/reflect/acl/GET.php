<?php

	use Reflect\Path;
	use Reflect\Endpoint;
	use Reflect\Response;

	use ReflectRules\Type;
	use ReflectRules\Rules;
	use ReflectRules\Ruleset;

	use Reflect\API\Controller;
	use Reflect\Database\Models\Acl\AclModel;
	use Reflect\Database\Models\Acl\MethodEnum;

	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Acl.php");

	class GET_ReflectAcl extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(AclModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(AclModel::REF_GROUP->value))
					->type(Type::NULL)
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(AclModel::REF_ENDPOINT->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(AclModel::METHOD->value))
					->type(Type::ENUM, array_column(MethodEnum::cases(), "name")),

				(new Rules(AclModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		public function main(): Response {
			return parent::return_list_response(
				$this->for(AclModel::TABLE)
				->where($_GET)
				->select([
					AclModel::ID->value,
					AclModel::REF_GROUP->value,
					AclModel::REF_ENDPOINT->value,
					AclModel::METHOD->value,
					AclModel::CREATED->value
				])
			);
		}
	}