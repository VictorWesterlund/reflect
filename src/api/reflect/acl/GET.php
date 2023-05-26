<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Connection;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectAcl extends AuthDB implements Endpoint {
        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Return ACL details by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT id, api_key, endpoint, method, created FROM api_acl WHERE id = ?";
                $res = $this->return_array($sql, $_GET["id"]);

                return !empty($res) 
                    ? new Response($res[0])
                    : new Response(["No record", "No ACL record found with id '{$_GET["id"]}'"], 404);
            }

            // Return array of all active Reflect API users
            $sql = "SELECT id, created FROM api_acl";
            return new Response($this->return_array($sql));
        }
    }