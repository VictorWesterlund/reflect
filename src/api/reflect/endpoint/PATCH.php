<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    require_once Path::reflect("src/request/Router.php");

    class PATCH_ReflectEndpoint implements Endpoint {
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
        }

        public function main(): Response {
            // Alias for PUT
            return Call("reflect/endpoint?id={$_GET["id"]}", Method::PUT, $_POST);
        }
    }