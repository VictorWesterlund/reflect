<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/request/Router.php");

    class DELETE_ReflectSessionUser implements Endpoint {
        const GET = [
            "id" => [
                "required" => true,
                "min"      => 1,
                "max"      => 128
            ]
        ];

        public function __construct() {
            // ...
        }

        // Attempt to delete current user
        public function main(): Response {
            // Get ID from current user
            $user = Call("reflect/session/user", Method::GET);
            if (!$user->ok) {
                return new Response(["Failed to delete user", $user], 500);
            }

            // Delete current user by ID
            $delete = Call("reflect/user?id={$user->output()}", Method::DELETE);
            return $delete->ok
                ? new Response("OK")
                : new Response(["Failed to delete user", $delete], 500);
        }
    }