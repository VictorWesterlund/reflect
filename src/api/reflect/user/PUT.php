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

    class PUT_ReflectUser extends AuthDB implements Endpoint {
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
            $sql = "UPDATE api_users SET active = ? WHERE id = ?";
            
            // Update the endpoint
            return $this->update("api_users", ["active" => $_POST["active"], [$_GET["id"]]])
                ? new Response("OK")
                : new Response(["Failed to update user", $updated], 500);
        }
    }