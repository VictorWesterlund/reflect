<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectAcl extends AuthDB implements Endpoint {
        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Return ACL details by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT api_key, endpoint, method, created FROM api_acl WHERE id = ?";
                $res = $this->return_array($sql, $_GET["id"]);

                return !empty($res) 
                    ? new Response($res[0])
                    : new Response(["No record", "No ACL record found with id '{$_GET["id"]}'"], 404);
            }

            // Return array of all active Reflect API users
            $sql = "SELECT api_key, endpoint, method, created FROM api_acl ORDER BY created DESC";
            return new Response($this->return_array($sql));
        }
    }