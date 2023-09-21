<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class PUT_ReflectEndpoint extends AuthDB implements Endpoint {
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

            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            $update = ["active"   => $_POST["active"]];
            $filter = ["endpoint" => $_GET["id"]];
            
            // Update the endpoint
            return $this->update("api_endpoints", $update, $filter)
                ? new Response("OK")
                : new Response(["Failed to update endpoint", $updated], 500);
        }
    }