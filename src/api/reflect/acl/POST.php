<?php

    use \Reflect\Path;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;
    use \Reflect\Request\Connection;
    use \Reflect\Database\AuthDB;

    require_once Path::reflect("src/request/Router.php");
    require_once Path::reflect("src/database/Auth.php");

    class POST_ReflectAcl extends AuthDB implements Endpoint {
        const POST = [
            "key"  => [
                "required" => true,
                "max"      => 32
            ],
            "endpoint" => [
                "required" => true,
                "max"      => 32
            ],
            "method"   => [
                "required" => true,
                "type"     => "text"
            ]
        ];

        public function __construct() {
            parent::__construct(Connection::INTERNAL);
        }

        // Generate truncated SHA256 hash to 32 chars of of input fields
        public static function get_acl_hash(): string {
            return substr(hash("sha256", implode("", [
                $_POST["key"],
                $_POST["endpoint"],
                $_POST["method"]->value
            ])), -32);
        }

        public function main(): Response {
            // Check endpoint is valid
            if (!Call("reflect/endpoint?id={$_POST["endpoint"]}", Method::GET)->ok) {
                return new Response("No endpoint with id '{$_POST["endpoint"]}' was found", 404);
            }

            // Check key is valid
            if (!Call("reflect/key?id={$_POST["key"]}", Method::GET)->ok) {
                return new Response("No API key with id '{$_POST["endpoint"]}' was found", 404);
            }

            // Attempt to resolve HTTP verb from uppercase string
            $_POST["method"] = Method::tryFrom(strtoupper($_POST["method"])) ?? new Response([
                "Method unsupported",
                "Method '{$_POST["method"]}' is not a supported HTTP verb",
                405
            ]);
            
            // Use hash of POST fields as id for ACL rule in database
            $hash = $this::get_acl_hash();

            // Check if the rule has already been granted
            if (Call("reflect/acl?id=${hash}", Method::GET)->ok) {
                return new Response("ACL rule already exists", 402);
            }

            $sql = "INSERT INTO api_acl (id, api_key, endpoint, method, created) VALUES (?, ?, ?, ?, ?)";
            $insert = $this->return_bool($sql, [
                $hash,
                $_POST["key"],
                $_POST["endpoint"],
                $_POST["method"]->value,
                time()
            ]);

            return $insert
                ? new Response("OK")
                : new Response("Failed to create ACL rule");
        }
    }