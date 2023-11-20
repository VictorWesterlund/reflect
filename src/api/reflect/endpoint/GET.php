<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;

    use \ReflectRules\Type;
    use \ReflectRules\Rules;
    use \ReflectRules\Ruleset;

    use \Reflect\Database\Database;
    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Endpoints.php");

    class GET_ReflectEndpoint extends Database implements Endpoint {
        private Ruleset $rules;

        public function __construct() {
            $this->rules = new Ruleset();

            $this->rules->GET([
                (new Rules("id"))
                    ->type(Type::STRING)
                    ->max(255)
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Check if endpoint exists by name
            if (!empty($_GET["id"])) {
                $endpoint = $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->where([
                        Model::ID->value => $_GET["id"]
                    ])
                    ->limit(1)
                    ->select(Model::values());

                return !empty($endpoint) 
                    ? new Response($endpoint)
                    : new Response(["No endpoint", "No endpoint found with name '{$_GET["id"]}'"], 404);
            }

            // Return array of all active Reflect API users
            return new Response(
                $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->select(Model::values())
            );
        }
    }