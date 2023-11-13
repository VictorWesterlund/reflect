<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \Reflect\Database\Database;
    use \Reflect\Database\Acl\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Acl.php");

    class POST_ReflectAcl extends Database implements Endpoint {
        public function __construct() {
            Rules::POST([
                "api_key"  => [
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
            ]);
            
            parent::__construct();
        }

        // Generate hash of ACL parameters.
        // This will prevent the same ACL being defined more than once due to UNIQUE constraint fail on id column
        public static function generate_hash(): string {
            return substr(hash("sha256", implode("", [
                $_POST["api_key"],
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
            if (!Call("reflect/key?id={$_POST["api_key"]}", Method::GET)->ok) {
                return new Response("No API key with id '{$_POST["endpoint"]}' was found", 404);
            }

            // Attempt to resolve HTTP verb from uppercase string
            $_POST["method"] = Method::tryFrom(strtoupper($_POST["method"])) ?? new Response([
                "Method unsupported",
                "Method '{$_POST["method"]}' is not a supported HTTP verb",
                405
            ]);

            // Check if the rule has already been granted
            if (Call("reflect/acl?api_key={$_POST["api_key"]}&endpoint={$_POST["endpoint"]}&method={$_POST["method"]->value}", Method::GET)->ok) {
                return new Response("ACL rule already exists", 402);
            }

            $insert = $this->for(Model::TABLE)
                ->with(Model::values())
                ->insert([
                    $this->generate_hash(),
                    $_POST["api_key"],
                    $_POST["endpoint"],
                    $_POST["method"]->value,
                    time()
                ]);

            return $insert ? new Response("OK") : new Response("Failed to create ACL rule");
        }
    }