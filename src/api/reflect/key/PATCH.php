<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/request/Router.php");

    class PATCH_ReflectKey implements Endpoint {
        const GET = [
            "id" => [
                "required" => true,
                "min"      => 1,
                "max"      => 128
            ]
        ];

        const POST = [
            "id"      => [
                "required" => false,
                "type"     => "string",
                "min"      => 1,
                "max"      => 128
            ],
            "user"    => [
                "required" => false,
                "type"     => "string"
            ],
            "expires" => [
                "required" => false,
                "type"     => "int",
                "max"      => PHP_INT_MAX
            ]
        ];

        public function __construct() {
            // ...
        }

        public function main(): Response {
            $update = Call("reflect/key?id={$_GET["id"]}", Method::PUT, $_POST);
            return $update->ok
                ? new Response("OK")
                : new Response(["Failed to update key", $update], 500);
        }
    }