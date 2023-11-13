<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \Reflect\Database\Endpoints\Model;

    require_once Path::reflect("src/database/model/Endpoints.php");

    class DELETE_ReflectEndpoint implements Endpoint {
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
            // Soft-delete endpoint by setting active to false
            $delete = Call("reflect/endpoint?id={$_GET["id"]}", Method::PUT, [
                Model::ACTIVE->value => false
            ]);
            
            return $delete->ok
                ? new Response("OK")
                : new Response(["Failed to delete key", $delete], 500);
        }
    }