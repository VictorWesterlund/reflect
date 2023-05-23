<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Connection;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectUser extends AuthDB implements Endpoint {
        const GET = [
            "id" => [
                "required" => false,
                "min"      => 1,
                "max"      => 128
            ]
        ];

        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Return bool if Reflect API user exists and is active by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT id, active, created FROM api_users WHERE id = ?";

                $res = $this->return_array($sql, $_GET["id"]);
                return !empty($res) 
                    ? new Response($res) 
                    : new Response(["No user", "No user with id '{$_GET["id"]}' was found"], 404);
            }

            // Return array of all active users
            $sql = "SELECT id, created FROM api_users WHERE active = 1";
            return new Response($this->return_array($sql));
        }
    }