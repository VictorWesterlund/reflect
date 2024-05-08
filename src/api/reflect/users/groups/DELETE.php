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
	use Reflect\Database\Models\UsersGroups\UsersGroupsModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/UsersGroups.php");

	class DELETE_ReflectUsersGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->POST([
				(new Rules(UsersGroupsModel::REF_USER->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(UsersGroupsModel::REF_GROUP->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if a user group relationship exists
		private function user_group_exists(): bool {
			return (new Call(Endpoints::USERS_GROUPS->endpoint()))
				->params([
					UsersGroupsModel::REF_USER->value  => $_POST[UsersGroupsModel::REF_USER->value],
					UsersGroupsModel::REF_GROUP->value => $_POST[UsersGroupsModel::REF_GROUP->value]
				])
				->get()->ok;
		}

		public function main(): Response {
			// Can not update entity for nonexistent user id
			if (!$this->user_group_exists()) {
				return new Response("Failed to delete user group relationship. User group relationship does not exist", 404);
			}

			return $this->for(UsersGroupsModel::TABLE)
				->where($_POST)
				->delete()
					// Return user id that was deleted if successful
					? new Response("", 204)
					: new Response(self::error_prefix(), 500);
		}
	}