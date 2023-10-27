<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \Reflect\Database\Database;
    use \Reflect\Database\Users\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Users.php");

    class POST_ReflectUser extends Database implements Endpoint {
        public function __construct() {
            Rules::POST([
                "id" => [
                    "required" => true,
                    "type"     => "string",
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Check if user already exists
            $user = Call("reflect/user?id={$_POST["id"]}", Method::GET);
            if ($user->ok) {
                return new Response("User already exists", 409);
            }

            // Attempt to add user
            $this->for(Model::TABLE)
                ->with(Model::values())
                ->insert([
                    $_POST["id"],
                    true,
                    time()
                ]);

            // Check if user got added sucessfully
            return Call("reflect/user?id={$_POST["id"]}", Method::GET)->ok
                ? new Response("OK")
                : new Response("Failed to add user", 500);
        }
    }