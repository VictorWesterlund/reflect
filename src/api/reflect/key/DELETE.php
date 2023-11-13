<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \Reflect\Database\Keys\Model;

    require_once Path::reflect("src/database/model/Keys.php");

    class DELETE_ReflectKey implements Endpoint {
        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => true,
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);
        }

        public function main(): Response {
            // Soft-delete key by setting active to false
            $delete = Call("reflect/key?id={$_GET["id"]}", Method::PUT, [
                Model::ACTIVE->value => false
            ]);
            
            return $delete->ok ? new Response("OK") : new Response(["Failed to delete key", $delete], 500);
        }
    }