<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Connection;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectEndpoint extends AuthDB implements Endpoint {
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
            // Check if endpoint exists by name
            if (!empty($_GET["id"])) {
                $sql = "SELECT endpoint, active FROM api_endpoints WHERE endpoint = ?";
                $res = $this->return_array($sql, $_GET["id"]);

                return !empty($res) 
                    ? new Response($res[0])
                    : new Response(["No endpoint", "No endpoint found with name '{$_GET["id"]}'"], 404);
            }

            // Return array of all active Reflect API users
            $sql = "SELECT endpoint, active FROM api_endpoints";
            return new Response($this->return_array($sql));
        }
    }