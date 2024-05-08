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
	use Reflect\Database\Models\Users\UsersModel;
	use Reflect\Database\Models\Groups\GroupsModel;
	use Reflect\Database\Models\UsersGroups\UsersGroupsModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Users.php");
	require_once Path::reflect("src/database/models/Groups.php");
	require_once Path::reflect("src/database/models/UsersGroups.php");

	class POST_ReflectUsersGroups extends Controller implements Endpoint {
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

		// Returns true if a user exists with the provided id
		private function user_exists(): bool {
			return (new Call(Endpoints::USERS->endpoint()))
				->params([UsersModel::ID->value => $_POST[UsersGroupsModel::REF_USER->value]])
				->get()->ok;
		}

		// Returns true if an API key exists with the provided id
		private function group_exists(): bool {
			return (new Call(Endpoints::GROUPS->endpoint()))
				->params([GroupsModel::ID->value => $_POST[UsersGroupsModel::REF_GROUP->value]])
				->get()->ok;
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
			// Can not create acl rule for nonexistent endpoint
			if (!$this->user_exists()) {
				return new Response("Failed to create user group relationship. User with id '{$_POST[UsersGroupsModel::REF_USER->value]}' does not exist", 404);
			}

			// Can not create acl rule for nonexistent group
			if (!$this->group_exists()) {
				return new Response("Failed to create user group relationship. Group with id '{$_POST[UsersGroupsModel::REF_GROUP->value]}' does not exist", 404);
			}

			// Bail out if this user and group already have a defined relationship
			if ($this->user_group_exists()) {
				return new Response("Failed to create user group relationship. User and group relationship already exist", 409);
			}

			return $this->for(UsersGroupsModel::TABLE)
				->insert($_POST)
				? new Response($_POST[UsersGroupsModel::REF_USER->value], 201)
				: new Response(self::error_prefix(), 500);
		}
	}