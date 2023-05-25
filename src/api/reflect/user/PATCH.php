<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/request/Router.php");

    class PATCH_ReflectUser implements Endpoint {
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
            return Call("reflect/user?id={$_GET["id"]}", Method::PUT, $_POST);
        }
    }