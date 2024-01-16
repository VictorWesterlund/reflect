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
                ->select(Model::USER->value);

            // No user data found
            if ($user->num_rows !== 1) {
                return new Response(null, 404);
            }

            return new Response($user->fetch_assoc()["user"]);
        }
    }