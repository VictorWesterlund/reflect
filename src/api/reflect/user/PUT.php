<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class PUT_ReflectUser extends AuthDB implements Endpoint {
        const GET = [
            "id" => [
                "required" => true,
                "min"      => 1,
                "max"      => 128
            ]
        ];

        const POST = [
            "active"   => [
                "required" => true,
                "type"     => "bool"
            ]
        ];

        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            $sql = "UPDATE api_users SET active = ? WHERE id = ?";
            
            // Update the endpoint
            $updated = $this->return_bool($sql, [$_POST["active"], $_GET["id"]]);
            return $updated
                ? new Response("OK")
                : new Response(["Failed to update user", $updated], 500);
        }
    }