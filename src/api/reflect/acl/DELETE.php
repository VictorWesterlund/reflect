<?php

    use \Reflect\Path;
    use \Reflect\Rules;
    use \Reflect\Endpoint;
    use \Reflect\Response;
    use function \Reflect\Call;
    use \Reflect\Request\Method;

    use \Reflect\Database\Acl\Model;

    require_once Path::reflect("src/database/Database.php");
    require_once Path::reflect("src/database/model/Acl.php");

    class DELETE_ReflectAcl extends Database implements Endpoint {
        public function __construct() {
            Rules::GET([
                "endpoint" => [
                    "required" => true,
                    "type"     => "text",
                    "min"      => 1,
                    "max"      => 128
                ],
                "method"   => [
                    "required" => true,
                    "type"     => "text"
                ],
                "api_key"      => [
                    "required" => true,
                    "type"     => "text",
                    "min"      => 1,
                    "max"      => 128
                ]
            ]);
            
            parent::__construct();
        }

        public function main(): Response {
            // Build qualified pathname and query from components
            $url = "reflect/acl?endpoint={$_GET["endpoint"]}&method={$_GET["method"]}&api_key={$_GET["api_key"]}";

            // Check if the ACL rule exists
            if (!Call($url, Method::GET)->ok) {
                return new Response("No matching ACL rule was found", 404);
            }

            // Attempt to delete rule from database
            $sql = "DELETE FROM api_acl WHERE endpoint = ? AND method = ? AND api_key = ?";
            $this->exec($sql, [
                $_GET["endpoint"],
                $_GET["method"],
                $_GET["api_key"]
            ]);

            // Run GET requet again to see if the rule has indeed been removed
            return !Call($url, Method::GET)->ok
                ? new Response("OK")
                : new Response("Failed to delete ACL rule", 500);
        }
    }