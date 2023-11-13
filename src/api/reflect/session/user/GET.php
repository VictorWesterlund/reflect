<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    use \Reflect\Database\Keys\Model;

    require_once Path::reflect("src/database/Auth.php");
    require_once Path::reflect("src/database/model/Keys.php");

    class GET_ReflectSessionUser extends AuthDB implements Endpoint {
        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        // Return the API key used for the current request
        public function main(): Response {
            // Get API key
            $key = $this->get_api_key();
            // Resolve user id from API key
            $user = $this->for(Model::TABLE)
                ->with(Model::values())
                ->where([Model::ID->value => $key])
                ->limit(1)
                ->flatten()
                ->select(Model::USER->value);

            return !empty($user)
                ? new Response($user["user"])
                /*
                    Current key is not in keys table.
                    This is probably a configuration error. The requester key had access in the ACL table to make the call, 
                    but the corresponding key does not exist. Restore from backup and make sure that foregin key constraints are enabled.
                */
                : new Response(["Unknown user", "No user found for current API key"], 404);
        }
    }