<?php

    use \Reflect\Path;
    use function \Reflect\Call;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class POST_ReflectEndpoint extends AuthDB implements Endpoint {
        const POST = [
            "endpoint" => [
                "required" => true,
                "type"     => "text",
                "min"      => 1,
                "max"      => 128
            ],
            "active"   => [
                "required" => false,
                "type"     => "bool"
            ]
        ];

        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        public function main(): Response {
            // Attempt to INSERT new endpoint
            $sql = "INSERT INTO api_endpoints (endpoint, active) VALUES (?, ?)";
            $this->return_bool($sql, [$_POST["endpoint"], 1]);

            // Ensure the endpoint was successfully created
            $created = Call("reflect/endpoint?id=${endpoint}", Method::GET);
            return $created->ok
                ? new Response("OK")
                : new Response(["Failed to create endpoint", $created], 500);
        }
    }