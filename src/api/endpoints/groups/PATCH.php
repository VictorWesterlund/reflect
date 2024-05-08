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
	use Reflect\Database\Models\Groups\GroupsModel;

	require_once Path::reflect("src/api/Endpoints.php");
	require_once Path::reflect("src/api/Controller.php");
	require_once Path::reflect("src/database/models/Groups.php");

	class PATCH_ReflectGroups extends Controller implements Endpoint {
		private Ruleset $ruleset;

		public function __construct() {
			$this->ruleset = new Ruleset(strict: true);

			$this->ruleset->GET([
				(new Rules(GroupsModel::ID->value))
					->required()
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE)
			]);

			$this->ruleset->POST([
				(new Rules(GroupsModel::ID->value))
					->type(Type::STRING)
					->min(1)
					->max(parent::MYSQL_VARCHAR_MAX_SIZE),

				(new Rules(GroupsModel::ACTIVE->value))
					->type(Type::BOOLEAN),

				(new Rules(GroupsModel::CREATED->value))
					->type(Type::NUMBER)
					->min(0)
					->max(parent::MYSQL_INT_MAX_SIZE)
			]);
			
			parent::__construct($this->ruleset);
		}

		// Returns true if a user exists with the provided id
		private function group_exists(string $id): bool {
			return (new Call(Endpoints::GROUPS->endpoint()))
				->params([GroupsModel::ID->value => $id])
				->get()->ok;
		}

		public function main(): Response {
			// Bail out as there is nothing to do with an empty request body
			if (empty($_POST)) {
				return new Response($_GET[GroupsModel::ID->value]);
			}

			// Can not update entity for nonexistent user id
			if (!$this->group_exists($_GET[GroupsModel::ID->value])) {
				return new Response("Failed to update group with id '{$_GET[GroupsModel::ID->value]}'. User does not exist", 404);
			}

			// Verify that an updated user id does not already exist
			if (array_key_exists(GroupsModel::ID->value, $_POST)) {
				if ($this->group_exists($_POST[GroupsModel::ID->value])) {
					return new Response("Failed to update group with id '{$_GET[GroupsModel::ID->value]}'. User with id '{$_POST[GroupsModel::ID->value]}' already exist", 409);
				}
			}

			return $this->for(GroupsModel::TABLE)
				->where([GroupsModel::ID->value => $_GET[GroupsModel::ID->value]])
				->update($_POST)
					// Return updated or existing user id if successful
					? new Response($_POST[GroupsModel::ID->value] ?? $_GET[GroupsModel::ID->value])
					: new Response(self::error_prefix(), 500);
		}
	}