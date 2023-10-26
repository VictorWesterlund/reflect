<?php

    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    class PATCH_ReflectKey implements Endpoint {
        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => true,
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);

            Rules::POST([
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
            ]);
        }

        public function main(): Response {
            $update = Call("reflect/key?id={$_GET["id"]}", Method::PUT, $_POST);

            // Get all values from $_POST that exist in self::POST
            //$values = array_map(fn($k): mixed => is_null($_POST[$k]) ? $key->output()[$k] : $_POST[$k], array_keys(self::POST));

            return $update->ok ? new Response("OK") : new Response("Failed to update key", 500);
        }
    }