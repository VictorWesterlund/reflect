<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Database;
    use \Reflect\Database\Users\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Users.php");

    class GET_ReflectUser extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->type(Type::STRING)
                    ->max(128)
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Filter only active users
            $filter = [
                Model::ACTIVE->value => 1
            ];

            // Return bool if user exists and is active by id
            if (!empty($_GET["id"])) {
                $filter[Model::ID->value] = $_GET["id"];

                // Get details for user by ID
                $user = $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->where($filter)
                    ->limit(1)
                    ->select(Model::values());

                return !empty($user) 
                    ? new Response($user) 
                    : new Response(["No user", "No user with id '{$_GET["id"]}' was found"], 404);
            }

            // Return array of all active users
            return new Response(
                $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->where($filter)
                    ->select(Model::values())
            );
        }
    }