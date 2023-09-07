<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectUser extends AuthDB implements Endpoint {
        private const COLUMNS = [
            "id",
            "active",
            "created"
        ];

        public function __construct() {
            Rules::GET([
                "id" => [
                    "required" => false,
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);
            
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Filter only active users
            $filter = [
                "active" => 1
            ];

            // Return bool if user exists and is active by id
            if (!empty($_GET["id"])) {
                $filter["id"] = $_GET["id"];
                $key = $this->get("api_users", self::COLUMNS, $filter, 1);

                return !empty($key) 
                    ? new Response($key) 
                    : new Response(["No user", "No user with id '{$_GET["id"]}' was found"], 404);
            }

            // Return array of all active users
            return new Response($this->get("api_users", self::COLUMNS, $filter));
        }
    }