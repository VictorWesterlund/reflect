<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectKey extends AuthDB implements Endpoint {
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
            // Return bool if Reflect API key exists and is active by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT id, user, active, expires, created FROM api_keys WHERE id = ? AND active = 1";

                $res = $this->return_array($sql, $_GET["id"]);
                return !empty($res) 
                    ? new Response($res[0]) 
                    : new Response(["No key", "No API key with id '{$_GET["id"]}' was found"], 404);
            }

            // Return array of all active keys
            $sql = "SELECT id, user, active, expires, created FROM api_keys WHERE active = 1";
            return new Response($this->return_array($sql));
        }
    }