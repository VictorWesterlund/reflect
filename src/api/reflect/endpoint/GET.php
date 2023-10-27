<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;

    use \Reflect\Database\Database;
    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Endpoints.php");

    class GET_ReflectEndpoint extends Database implements Endpoint {
        private const COLUMNS = [
            "endpoint",
            "active"
        ];

        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => false,
                    "min"      => 1,
                    "max"      => 128
                ]
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