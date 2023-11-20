<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Database;
    use \Reflect\Database\Keys\Model;
    
    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Keys.php");

    class GET_ReflectKey extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->required()
                    ->type(Type::STRING)
                    ->max(255)
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Filter only active API keys
            $filter = [
                Model::ACTIVE->value => 1
            ];

            // Return bool if Reflect API key exists and is active by id
            if (!empty($_GET["id"])) {
                $filter[Model::ID->value] = $_GET["id"];

                // Get key details by ID
                $key = $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->where($filter)
                    ->limit(1)
                    ->select(Model::values());

                return !empty($key) 
                    ? new Response($key) 
                    : new Response(["No key", "No API key with id '{$_GET["id"]}' was found"], 404);
            }

            // Return array of all active keys
            return new Response(
                $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->select(Model::values())
            );
        }
    }