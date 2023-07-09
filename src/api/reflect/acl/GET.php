<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class GET_ReflectAcl extends AuthDB implements Endpoint {
        const GET = [
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

        public function __construct() {
            Rules::GET(self::GET);

            parent::__construct(Connection::INTERNAL);
        }

        // Return array of truthy search params
        private function filter(): array {
            $filters = [];

            // Add search param to $filters if search param with same key is not falsy
            foreach (array_keys(self::GET) as $key) {
                if (!empty($_GET[$key])) {
                    $filters[] = $key;
                }
            }

            return $filters;
        }

        public function main(): Response {
            $filter = $this->filter();

            // Return ACL details by search parameters
            if (!empty($filter)) {
                // Generate SELECT values for prepared statement
                // TODO: This is dumb and should be handled by the database library!
                $values = array_map(fn($v): string => "${v} = ?", $filter);
                $values = implode(" AND ", $values);

                $sql = "SELECT endpoint, method, api_key FROM api_acl WHERE ${values}";
                $res = $this->return_array($sql, array_map(fn($k): string => $_GET[$k], $filter));

                return !empty($res) 
                    ? new Response($res[0])
                    : new Response(["No record", "No ACL record found with parameters'"], 404);
            }

            // Return array of all active Reflect API users only if none of the search parameters have been set
            $sql = "SELECT api_key, endpoint, method, created FROM api_acl ORDER BY created DESC";
            return new Response($this->return_array($sql));
        }
    }