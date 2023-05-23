<?php

    use \Reflect\Path;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/request/Router.php");

    class DELETE_ReflectEndpoint implements Endpoint {
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

        public function main(): Response {
            // Soft-delete endpoint by setting active to false
            return Call("reflect/endpoint?id={$_GET["id"]}", Method::PUT, [
                "active" => false
            ]);
        }
    }