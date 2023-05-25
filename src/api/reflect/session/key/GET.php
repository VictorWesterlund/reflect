<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Connection;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectSessionKey extends AuthDB implements Endpoint {
        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        // Return the API key used for the current request
        public function main(): Response {
            return new Response($this->get_api_key());
        }
    }