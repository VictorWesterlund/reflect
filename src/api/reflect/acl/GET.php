<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    use \Reflect\Database\Acl\Model;

    require_once Path::reflect("src/database/Auth.php");
    require_once Path::reflect("src/database/model/Acl.php");

    class GET_ReflectAcl extends AuthDB implements Endpoint {
        private const GET = [
            "endpoint" => [
                "required" => false,
                "type"     => "text",
                "min"      => 1,
                "max"      => 128
            ],
            "method"   => [
                "required" => false,
                "type"     => "text"
            ],
            "api_key"      => [
                "required" => false,
                "type"     => "text",
                "min"      => 1,
                "max"      => 128
            ]
        ];

        // Return these columns from the ACL table
        private const COLUMNS = [
            "api_key",
            "endpoint",
            "method",
            "created"
        ];

        public function __construct() {
            Rules::GET(self::GET);

            parent::__construct(Connection::INTERNAL);
        }

        // Get ACL rule from database by endpoint, method, and API key
        private function get_rule(): array {
            $filter = array_combine(array_keys(self::GET), [
                $_GET["endpoint"],
                $_GET["method"],
                $_GET["api_key"]
            ]);

            // Get ACL rule from database
            return $this->for(Model::TABLE)
                ->with(Model::values())
                ->where($filter)
                ->limit(1)
                ->select(Model::values());
        }

        public function main(): Response {
            // Make sure if search parameters are set, that they are all there
            $args = array_filter(array_values($_GET));
            if ($args && count($args) !== count(self::GET)) {
                return new Response("Missing required GET parameters", 422);
            }

            // Return specific ACL rule by search parameters
            if ($args) {
                $acl_rule = $this->get_rule();

                return !empty($acl_rule) 
                    ? new Response($acl_rule)
                    : new Response(["No record", "No ACL record found with parameters'"], 404);
            }

            // Return array of all active Reflect API users only if none of the search parameters have been set
            return new Response(
                $this->for(Model::TABLE)
                    ->with(Model::values())
                    ->select(Model::values())
            );
        }
    }