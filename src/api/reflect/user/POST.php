<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;
    use \Reflect\Request\Connection;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class POST_ReflectUser extends AuthDB implements Endpoint {
        const POST = [
            "id" => [
                "required" => true,
                "type"     => "string",
                "min"      => 1,
                "max"      => 128
            ]
        ];

        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Check if user already exists
            $user = Call("reflect/user?id={$_POST["id"]}", Method::GET);
            if ($user->ok) {
                return new Response("User already exists", 409);
            }

            // Attempt to add user
            $sql = "INSERT INTO api_users (id, active, created) VALUES (?, ?, ?)";
            $this->return_bool($sql, [
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