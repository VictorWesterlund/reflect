<?php

    use \Reflect\Path;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/request/Router.php");

    class PATCH_ReflectEndpoint implements Endpoint {
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
            // ...
        }

        public function main(): Response {
            // Alias for PUT
            return Call("reflect/endpoint?id={$_GET["id"]}", Method::PUT, $_POST);
        }
    }