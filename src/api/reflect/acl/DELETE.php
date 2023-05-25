<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class DELETE_ReflectAcl extends AuthDB implements Endpoint {
        const GET = [
            "id" => [
                "required" => true,
                "min"      => 1,
                "max"      => 128
            ]
        ];

        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Check if the ACL rule exists
            if (!Call("reflect/acl?id={$_GET["id"]}", Method::GET)->ok) {
                return new Response("No ACL rule with id '{$_GET["id"]}' was found", 404);
            }

            // Attempt to delete rule from database
            $sql = "DELETE FROM api_acl WHERE id = ?";
            $this->return_bool($sql, $_GET["id"]);

            // Run GET requet again to see if the rule has indeed been removed
            return !Call("reflect/acl?id={$_GET["id"]}", Method::GET)->ok
                ? new Response("OK")
                : new Response("Failed to delete ACL rule", 500);
        }
    }