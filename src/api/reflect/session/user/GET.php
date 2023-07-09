<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectSessionUser extends AuthDB implements Endpoint {
        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        // Return the API key used for the current request
        public function main(): Response {
            // Resolve user id from current key
            $sql = "SELECT user FROM api_keys WHERE id = ?";
            $res = $this->return_array($sql, $this->get_api_key());

            return !empty($res)
                ? new Response($res[0]["user"])
                /*
                    Current key is not in keys table.
                    This is probably a configuration error. The requester key had access in the ACL table to make the call, 
                    but the corresponding key does not exist. Restore from backup and make sure that foregin key constraints are enabled.
                */
                : new Response(["Unknown user", "No user found for current API key"], 404);
        }
    }