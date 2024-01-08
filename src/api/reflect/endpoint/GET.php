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
            // Request parameters are invalid, bail out here
            if (!$this->rules->is_valid()) {
                return new Response($this->rules->get_errors(), 422);    
            }

            // Check if endpoint exists by name
            if (!empty($_GET["id"])) {
                $endpoint = $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->where([
                        Model::ID->value => $_GET["id"]
                    ])
                    ->limit(1)
                    ->select(Model::values());

                if ($endpoint->num_rows !== 1) {
                    return new Response(["No endpoint", "No endpoint found with name '{$_GET["id"]}'"], 404);
                }

                return new Response($endpoint->fetch_assoc());
            }

            // Return array of all active Reflect API users
            return new Response(
                $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->select(Model::values())->fetch_assoc()
            );
        }
    }