<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;

    use \Reflect\Database\Database;
    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Endpoints.php");

    class PUT_ReflectEndpoint extends Database implements Endpoint {
        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => true,
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);

            Rules::POST([
                "active"   => [
                    "required" => true,
                    "type"     => "bool"
                ]
            ]);

            parent::__construct();
        }

        public function main(): Response {
            $update = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([
                    Model::ID->value => $_GET["id"]
                ])
                ->update([
                    Model::ACTIVE->value => $_POST["active"]
                ]);
            
            // Update the endpoint
            return $update ? new Response("OK") : new Response("Failed to update endpoint", 500);
        }
    }